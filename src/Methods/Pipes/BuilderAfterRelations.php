<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use NunoMaduro\Larastan\Reflection\ModelScopeMethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;

final class BuilderAfterRelations implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();
        $isRelationSubclass = $classReflection->isSubclassOf(Relation::class);

        $found = false;

        if ($isRelationSubclass && ! $classReflection->isAbstract()) {
            if (($returnMethodReflection = $this->getMethodReflectionForBuilder($passable, EloquentBuilder::class))
                || ($returnMethodReflection = $this->getMethodReflectionForBuilder($passable, QueryBuilder::class))) {
                $passable->setMethodReflection($returnMethodReflection);
                $found = true;
            } else {
                $passable->setMethodReflection(
                    new ModelScopeMethodReflection(
                        $passable->getMethodName(),
                        $passable->getBroker()->getClass(Model::class),
                        $classReflection
                    )
                );

                $found = true;
            }
        }

        if (! $found) {
            $next($passable);
        }
    }

    /**
     * @param class-string $builderClass
     */
    private function getMethodReflectionForBuilder(PassableContract $passable, string $builderClass): ?EloquentBuilderMethodReflection
    {
        $eloquentBuilder = $passable->getBroker()->getClass($builderClass);
        $builderHelper = new BuilderHelper();
        $returnObject = new ObjectType($passable->getClassReflection()->getName());

        if (! $eloquentBuilder->hasNativeMethod($passable->getMethodName())) {
            if ($returnMethodReflection = $builderHelper->dynamicWhere($eloquentBuilder, $passable->getMethodName(), $returnObject)) {
                return $returnMethodReflection;
            }

            return null;
        }

        $methodReflection = $eloquentBuilder->getNativeMethod($passable->getMethodName());

        /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $originalWhereVariant */
        $originalWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        if (! in_array($builderClass, $originalWhereVariant->getReturnType()->getReferencedClasses())) {
            return null;
        }

        return new EloquentBuilderMethodReflection(
            $passable->getMethodName(),
            $eloquentBuilder,
            $originalWhereVariant->getParameters(),
            $returnObject
        );
    }
}

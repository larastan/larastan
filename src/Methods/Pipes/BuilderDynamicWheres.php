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
use Illuminate\Support\Str;
use PHPStan\Type\ObjectType;
use Illuminate\Database\Query\Builder;
use PHPStan\Reflection\ParametersAcceptorSelector;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;

/**
 * @internal
 */
final class BuilderDynamicWheres implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();
        $found = false;
        $isInstanceOfBuilder = $classReflection->getName() === Builder::class || $classReflection->isSubclassOf(
                Builder::class
            );

        if ($isInstanceOfBuilder && Str::startsWith($passable->getMethodName(), 'where')) {
            $methodReflection = $classReflection->getNativeMethod('dynamicWhere');

            /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $originalDynamicWhereVariant */
            $originalDynamicWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            $returnMethodReflection = new EloquentBuilderMethodReflection(
                $passable->getMethodName(), $classReflection,
                [$originalDynamicWhereVariant->getParameters()[1]],
                new ObjectType(EloquentBuilder::class)
            );

            $passable->setMethodReflection($returnMethodReflection);

            $found = true;
        }

        if (! $found) {
            $next($passable);
        }
    }
}

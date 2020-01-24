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

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Broker\Broker;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;

class BuilderHelper
{
    /** @var string[] */
    public const MODEL_RETRIEVAL_METHODS = ['first', 'find', 'findMany', 'findOrFail', 'firstOrFail'];

    /** @var string[] */
    public const MODEL_CREATION_METHODS = ['make', 'create', 'forceCreate', 'findOrNew', 'firstOrNew', 'updateOrCreate', 'fromQuery', 'firstOrCreate'];

    /**
     * @var Broker
     */
    private $broker;

    public function __construct(Broker $broker)
    {
        $this->broker = $broker;
    }

    public function dynamicWhere(
        string $methodName,
        ?ObjectType $returnObject = null
    ): ?EloquentBuilderMethodReflection {
        $classReflection = $this->broker->getClass(QueryBuilder::class);

        if (! Str::startsWith($methodName, 'where')) {
            return null;
        }

        $methodReflection = $classReflection->getNativeMethod('dynamicWhere');

        /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $originalDynamicWhereVariant */
        $originalDynamicWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            [$originalDynamicWhereVariant->getParameters()[1]],
            $returnObject ?? new ObjectType(EloquentBuilder::class)
        );
    }

    public function searchOnEloquentBuilder(string $methodName, string $modelClassName): ?MethodReflection
    {
        $model = $this->broker->getClass($modelClassName);

        if ($model->hasNativeMethod('scope'.ucfirst($methodName))) {
            $methodReflection = $model->getNativeMethod('scope'.ucfirst($methodName));
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            $parameters = $parametersAcceptor->getParameters();
            // We shift the parameters,
            // because first parameter is the Builder
            array_shift($parameters);

            $returnType = $parametersAcceptor->getReturnType();

            return new EloquentBuilderMethodReflection(
                'scope'.ucfirst($methodName), $methodReflection->getDeclaringClass(),
                $parameters,
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        $eloquentBuilder = $this->broker->getClass(EloquentBuilder::class);

        if (! $eloquentBuilder->hasNativeMethod($methodName)) {
            return null;
        }

        if (in_array($methodName, array_merge(self::MODEL_CREATION_METHODS, self::MODEL_RETRIEVAL_METHODS), true)) {
            $methodReflection = $eloquentBuilder->getNativeMethod($methodName);
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
            $returnType = ModelTypeHelper::replaceStaticTypeWithModel($parametersAcceptor->getReturnType(), $modelClassName);

            return new EloquentBuilderMethodReflection(
                $methodName, $eloquentBuilder,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        return $eloquentBuilder->getNativeMethod($methodName);
    }

    public function searchOnQueryBuilder(string $methodName, string $modelClassName): ?MethodReflection
    {
        $queryBuilder = $this->broker->getClass(QueryBuilder::class);

        if ($queryBuilder->hasNativeMethod($methodName)) {
            return $queryBuilder->getNativeMethod($methodName);
        }

        return null;
    }
}

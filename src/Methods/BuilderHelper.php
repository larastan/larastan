<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class BuilderHelper
{
    /** @var string[] */
    public const MODEL_RETRIEVAL_METHODS = ['first', 'find', 'findMany', 'findOrFail', 'firstOrFail', 'sole'];

    /** @var string[] */
    public const MODEL_CREATION_METHODS = ['make', 'create', 'forceCreate', 'findOrNew', 'firstOrNew', 'updateOrCreate', 'firstOrCreate'];

    /**
     * The methods that should be returned from query builder.
     *
     * @var string[]
     */
    public $passthru = [
        'average', 'avg',
        'count',
        'dd', 'dump',
        'doesntExist', 'exists',
        'getBindings', 'getConnection', 'getGrammar',
        'insert', 'insertGetId', 'insertOrIgnore', 'insertUsing',
        'max', 'min',
        'raw',
        'sum',
        'toSql',
    ];

    /** @var ReflectionProvider */
    private $reflectionProvider;

    /** @var bool */
    private $checkProperties;

    public function __construct(ReflectionProvider $reflectionProvider, bool $checkProperties)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->checkProperties = $checkProperties;
    }

    public function dynamicWhere(
        string $methodName,
        Type $returnObject
    ): ?EloquentBuilderMethodReflection {
        if (! Str::startsWith($methodName, 'where')) {
            return null;
        }

        if ($returnObject instanceof GenericObjectType && $this->checkProperties) {
            $returnClassReflection = $returnObject->getClassReflection();

            if ($returnClassReflection !== null) {
                $modelType = $returnClassReflection->getActiveTemplateTypeMap()->getType('TModelClass');

                if ($modelType === null) {
                    $modelType = $returnClassReflection->getActiveTemplateTypeMap()->getType('TRelatedModel');
                }

                if ($modelType !== null) {
                    $finder = substr($methodName, 5);

                    $segments = preg_split(
                        '/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE
                    );

                    if ($segments !== false) {
                        $trinaryLogic = TrinaryLogic::createYes();

                        foreach ($segments as $segment) {
                            if ($segment !== 'And' && $segment !== 'Or') {
                                $trinaryLogic = $trinaryLogic->and($modelType->hasProperty(Str::snake($segment)));
                            }
                        }

                        if (! $trinaryLogic->yes()) {
                            return null;
                        }
                    }
                }
            }
        }

        $classReflection = $this->reflectionProvider->getClass(QueryBuilder::class);

        $methodReflection = $classReflection->getNativeMethod('dynamicWhere');

        /** @var FunctionVariantWithPhpDocs $originalDynamicWhereVariant */
        $originalDynamicWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        $originalParameter = $originalDynamicWhereVariant->getParameters()[1];

        $actualParameter = new DummyParameter($originalParameter->getName(), new MixedType(), $originalParameter->isOptional(), $originalParameter->passedByReference(), $originalParameter->isVariadic(), $originalParameter->getDefaultValue());

        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            $methodReflection,
            [$actualParameter],
            $returnObject,
            true
        );
    }

    /**
     * This method mimics the `EloquentBuilder::__call` method.
     * Does not handle the case where $methodName exists in `EloquentBuilder`,
     * that should be checked by caller before calling this method.
     *
     * @param  ClassReflection  $eloquentBuilder  Can be `EloquentBuilder` or a custom builder extending it.
     * @param  string  $methodName
     * @param  ClassReflection  $model
     * @return MethodReflection|null
     *
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function searchOnEloquentBuilder(ClassReflection $eloquentBuilder, string $methodName, ClassReflection $model): ?MethodReflection
    {
        // Check for local query scopes
        if ($model->hasNativeMethod('scope'.ucfirst($methodName))) {
            $methodReflection = $model->getNativeMethod('scope'.ucfirst($methodName));
            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            $parameters = $parametersAcceptor->getParameters();
            // We shift the parameters,
            // because first parameter is the Builder
            array_shift($parameters);

            $returnType = $parametersAcceptor->getReturnType();

            return new EloquentBuilderMethodReflection(
                'scope'.ucfirst($methodName),
                $methodReflection->getDeclaringClass(),
                $methodReflection,
                $parameters,
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        $queryBuilderReflection = $this->reflectionProvider->getClass(QueryBuilder::class);

        if (in_array($methodName, $this->passthru, true)) {
            return $queryBuilderReflection->getNativeMethod($methodName);
        }

        if ($queryBuilderReflection->hasNativeMethod($methodName)) {
            return $queryBuilderReflection->getNativeMethod($methodName);
        }

        return $this->dynamicWhere($methodName, new GenericObjectType($eloquentBuilder->getName(), [new ObjectType($model->getName())]));
    }

    /**
     * @param  string  $modelClassName
     * @return string
     *
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function determineBuilderName(string $modelClassName): string
    {
        $method = $this->reflectionProvider->getClass($modelClassName)->getNativeMethod('newEloquentBuilder');

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if (in_array(EloquentBuilder::class, $returnType->getReferencedClasses(), true)) {
            return EloquentBuilder::class;
        }

        if ($returnType instanceof ObjectType) {
            return $returnType->getClassName();
        }

        return $returnType->describe(VerbosityLevel::value());
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function determineCollectionClassName(string $modelClassName): string
    {
        $newCollectionMethod = $this->reflectionProvider->getClass($modelClassName)->getNativeMethod('newCollection');

        $returnType = ParametersAcceptorSelector::selectSingle($newCollectionMethod->getVariants())->getReturnType();

        if ($returnType instanceof ObjectType) {
            return $returnType->getClassName();
        }

        return $returnType->describe(VerbosityLevel::value());
    }
}

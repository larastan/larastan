<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Larastan\Larastan\Reflection\AnnotationScopeMethodParameterReflection;
use Larastan\Larastan\Reflection\DynamicWhereParameterReflection;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function array_key_exists;
use function array_shift;
use function count;
use function in_array;
use function preg_split;
use function substr;
use function ucfirst;

use const PREG_SPLIT_DELIM_CAPTURE;

class BuilderHelper
{
    public const MODEL_RETRIEVAL_METHODS = ['first', 'find', 'findMany', 'findOrFail', 'firstOrFail', 'sole'];

    public const MODEL_CREATION_METHODS = ['make', 'create', 'forceCreate', 'findOrNew', 'firstOrNew', 'updateOrCreate', 'firstOrCreate', 'createOrFirst'];

    /**
     * The methods that should be returned from query builder.
     *
     * @var string[]
     */
    public array $passthru = [
        'average',
        'avg',
        'count',
        'dd',
        'dump',
        'doesntExist',
        'exists',
        'getBindings',
        'getConnection',
        'getGrammar',
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'insertUsing',
        'max',
        'min',
        'raw',
        'sum',
        'toSql',
        'toRawSql',
        'dumpRawSql',
        'ddRawSql',
    ];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private bool $checkProperties,
        private MacroMethodsClassReflectionExtension $macroMethodsClassReflectionExtension,
    ) {
    }

    public function dynamicWhere(
        string $methodName,
        Type $returnObject,
    ): EloquentBuilderMethodReflection|null {
        if (! Str::startsWith($methodName, 'where')) {
            return null;
        }

        if (count($returnObject->getObjectClassReflections()) > 0 && $this->checkProperties) {
            $returnClassReflection = $returnObject->getObjectClassReflections()[0];

            $modelType = $returnClassReflection->getActiveTemplateTypeMap()->getType('TModel')
                ?? $returnClassReflection->getActiveTemplateTypeMap()->getType('TRelatedModel');

            if ($modelType !== null) {
                $finder = substr($methodName, 5);

                $segments = preg_split('/(And|Or)(?=[A-Z])/', $finder, -1, PREG_SPLIT_DELIM_CAPTURE);

                if ($segments !== false) {
                    $trinaryLogic = TrinaryLogic::createYes();

                    foreach ($segments as $segment) {
                        if ($segment === 'And' || $segment === 'Or') {
                            continue;
                        }

                        $trinaryLogic = $trinaryLogic->and($modelType->hasProperty(Str::snake($segment)));
                    }

                    if (! $trinaryLogic->yes()) {
                        return null;
                    }
                }
            }
        }

        $classReflection = $this->reflectionProvider->getClass(QueryBuilder::class);

        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            [new DynamicWhereParameterReflection()],
            $returnObject,
            true,
        );
    }

    /**
     * This method mimics the `EloquentBuilder::__call` method.
     * Does not handle the case where $methodName exists in `EloquentBuilder`,
     * that should be checked by caller before calling this method.
     *
     * @param  ClassReflection $eloquentBuilder Can be `EloquentBuilder` or a custom builder extending it.
     *
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function searchOnEloquentBuilder(ClassReflection $eloquentBuilder, string $methodName, Type $modelType): MethodReflection|null
    {
        // Check for macros first
        if ($this->macroMethodsClassReflectionExtension->hasMethod($eloquentBuilder, $methodName)) {
            return $this->macroMethodsClassReflectionExtension->getMethod($eloquentBuilder, $methodName);
        }

        $scopeName = 'scope' . ucfirst($methodName);

        foreach ($modelType->getObjectClassReflections() as $reflection) {
            // Check for Scope attribute
            if ($reflection->hasNativeMethod($methodName)) {
                $methodReflection  = $reflection->getNativeMethod($methodName);
                $hasScopeAttribute = false;
                foreach ($methodReflection->getAttributes() as $attribute) {
                    // using string instead of class constant to avoid failing on older Laravel versions
                    if ($attribute->getName() === 'Illuminate\Database\Eloquent\Attributes\Scope') {
                        $hasScopeAttribute = true;
                        break;
                    }
                }

                if (! $methodReflection->isPublic() && $hasScopeAttribute) {
                    $parametersAcceptor = $methodReflection->getVariants()[0];

                    $parameters = $parametersAcceptor->getParameters();
                    // We shift the parameters,
                    // because first parameter is the Builder
                    array_shift($parameters);

                    $returnType = $parametersAcceptor->getReturnType();

                    return new EloquentBuilderMethodReflection(
                        $methodName,
                        $methodReflection->getDeclaringClass(),
                        $parameters,
                        $returnType,
                        $parametersAcceptor->isVariadic(),
                    );
                }
            }

            // Check for @method phpdoc tags
            if (array_key_exists($scopeName, $reflection->getMethodTags())) {
                $methodTag = $reflection->getMethodTags()[$scopeName];

                $parameters = [];
                foreach ($methodTag->getParameters() as $parameterName => $parameterTag) {
                    $parameters[] = new AnnotationScopeMethodParameterReflection(
                        $parameterName,
                        $parameterTag->getType(),
                        $parameterTag->passedByReference(),
                        $parameterTag->isOptional(),
                        $parameterTag->isVariadic(),
                        $parameterTag->getDefaultValue(),
                    );
                }

                // We shift the parameters,
                // because first parameter is the Builder
                array_shift($parameters);

                return new EloquentBuilderMethodReflection(
                    $scopeName,
                    $reflection,
                    $parameters,
                    $methodTag->getReturnType(),
                );
            }

            if ($reflection->hasNativeMethod($scopeName)) {
                $methodReflection   = $reflection->getNativeMethod($scopeName);
                $parametersAcceptor = $methodReflection->getVariants()[0];

                $parameters = $parametersAcceptor->getParameters();
                // We shift the parameters,
                // because first parameter is the Builder
                array_shift($parameters);

                $returnType = $parametersAcceptor->getReturnType();

                return new EloquentBuilderMethodReflection(
                    $scopeName,
                    $methodReflection->getDeclaringClass(),
                    $parameters,
                    $returnType,
                    $parametersAcceptor->isVariadic(),
                );
            }
        }

        $queryBuilderReflection = $this->reflectionProvider->getClass(QueryBuilder::class);

        if (in_array($methodName, $this->passthru, true)) {
            return $queryBuilderReflection->getNativeMethod($methodName);
        }

        if ($queryBuilderReflection->hasNativeMethod($methodName)) {
            return $queryBuilderReflection->getNativeMethod($methodName);
        }

        // Check for query builder macros
        if ($this->macroMethodsClassReflectionExtension->hasMethod($queryBuilderReflection, $methodName)) {
            return $this->macroMethodsClassReflectionExtension->getMethod($queryBuilderReflection, $methodName);
        }

        return $this->dynamicWhere($methodName, new GenericObjectType($eloquentBuilder->getName(), [$modelType]));
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function determineBuilderName(string $modelClassName): string
    {
        $modelReflection = $this->reflectionProvider->getClass($modelClassName);
        $method          = $modelReflection->getNativeMethod('newEloquentBuilder');

        if ($method->getDeclaringClass()->getName() === Model::class) {
            $attrs = $modelReflection->getNativeReflection()->getAttributes('Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder'); //@phpstan-ignore argument.type (Attribute class might not exist)

            if ($attrs !== []) {
                $expr =  $attrs[0]->getArgumentsExpressions()[0];

                if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
                    return $expr->class->toString();
                }
            }
        }

        $returnType = $method->getVariants()[0]->getReturnType();

        if (in_array(EloquentBuilder::class, $returnType->getReferencedClasses(), true)) {
            return EloquentBuilder::class;
        }

        $classNames = $returnType->getObjectClassNames();

        if (count($classNames) === 1) {
            return $classNames[0];
        }

        return $returnType->describe(VerbosityLevel::value());
    }
}

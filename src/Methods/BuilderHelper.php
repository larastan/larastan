<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Larastan\Larastan\Reflection\AnnotationScopeMethodParameterReflection;
use Larastan\Larastan\Reflection\DynamicWhereParameterReflection;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function array_key_exists;
use function array_shift;
use function count;
use function defined;
use function in_array;
use function ltrim;
use function preg_split;
use function substr;
use function ucfirst;
use function version_compare;

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
        'aggregate',
        'average',
        'avg',
        'count',
        'dd',
        'dump',
        'doesntExist',
        'doesntExistOr',
        'exists',
        'existsOr',
        'explain',
        'getBindings',
        'getConnection',
        'getGrammar',
        'getRawBindings',
        'implode',
        'insert',
        'insertGetId',
        'insertOrIgnore',
        'insertOrIgnoreUsing',
        'insertUsing',
        'max',
        'min',
        'numericAggregate',
        'raw',
        'rawValue',
        'sum',
        'toSql',
        'toRawSql',
        'dumpRawSql',
        'ddRawSql',
    ];

    /** @var array<string, string> */
    private array $builderNameCache = [];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private bool $checkProperties,
        private MacroMethodsClassReflectionExtension $macroMethodsClassReflectionExtension,
    ) {
    }

    /** @return string[] */
    public function getPassthru(): array
    {
        // @phpstan-ignore-next-line
        if (! defined('LARAVEL_VERSION') || version_compare(LARAVEL_VERSION, '12.15.0', '<')) {
            return $this->passthru;
        }

        // @phpstan-ignore-next-line
        $passthru   = $this->passthru;
        $passthru[] = 'getCountForPagination';

        return $passthru;
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

                        $trinaryLogic = $trinaryLogic->and($modelType->hasInstanceProperty(Str::snake($segment)));
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

        $queryBuilderReflection = $this->resolveQueryBuilderReflection($eloquentBuilder);

        if (in_array($methodName, $this->getPassthru(), true)) {
            return $queryBuilderReflection->getNativeMethod($methodName);
        }

        if ($queryBuilderReflection->hasNativeMethod($methodName)) {
            return $queryBuilderReflection->getNativeMethod($methodName);
        }

        // Check for query builder macros
        if ($this->macroMethodsClassReflectionExtension->hasMethod($queryBuilderReflection, $methodName)) {
            return $this->macroMethodsClassReflectionExtension->getMethod($queryBuilderReflection, $methodName);
        }

        return $this->dynamicWhere($methodName, $this->getBuilderType($eloquentBuilder->getName(), $modelType));
    }

    /**
     * Resolve the query builder class actually used by the given Eloquent builder.
     *
     * Custom Eloquent builders (e.g. from packages replacing the underlying query layer,
     * such as mongodb/laravel-mongodb) commonly advertise this with their own
     * `@method CustomQueryBuilder toBase()` PHPDoc tag, overriding the inherited native
     * `toBase(): \Illuminate\Database\Query\Builder` declaration. `getMethod()` already
     * resolves `@method` tags (which take precedence over, and are invisible to, native
     * method reflection) as well as native methods, so methods that only exist on the
     * custom query builder (and are forwarded to it via `__call`) can still be resolved.
     */
    public function resolveQueryBuilderReflection(ClassReflection $eloquentBuilder): ClassReflection
    {
        if ($eloquentBuilder->hasMethod('toBase')) {
            $returnType = $eloquentBuilder->getMethod('toBase', new OutOfClassScope())->getVariants()[0]->getReturnType();
            $classNames = $returnType->getObjectClassNames();

            if (count($classNames) === 1) {
                return $this->reflectionProvider->getClass($classNames[0]);
            }
        }

        return $this->reflectionProvider->getClass(QueryBuilder::class);
    }

    public function getBuilderType(string $builderClassName, Type $modelType): ObjectType
    {
        if (! $this->reflectionProvider->getClass($builderClassName)->isGeneric()) {
            return new ObjectType($builderClassName);
        }

        return new GenericObjectType($builderClassName, [$modelType]);
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws InvalidArgumentException
     */
    public function determineBuilderName(string $modelClassName): string
    {
        if (array_key_exists($modelClassName, $this->builderNameCache)) {
            return $this->builderNameCache[$modelClassName];
        }

        $modelReflection = $this->reflectionProvider->getClass($modelClassName);

        if (! $modelReflection->is(Model::class)) {
            throw new InvalidArgumentException($modelClassName . ' is not a Model.');
        }

        $method = $modelReflection->getNativeMethod('newEloquentBuilder');

        if ($method->getDeclaringClass()->getName() === Model::class) {
            $builderClassName = $this->findUseEloquentBuilderClassName($modelReflection);

            // Like Model::newEloquentBuilder(), ignore a class that is not an Eloquent builder.
            if ($builderClassName !== null && $this->reflectionProvider->hasClass($builderClassName) && $this->reflectionProvider->getClass($builderClassName)->is(EloquentBuilder::class)) {
                return $this->builderNameCache[$modelClassName] = $builderClassName;
            }
        }

        $returnType = $method->getVariants()[0]->getReturnType();

        if (in_array(EloquentBuilder::class, $returnType->getReferencedClasses(), true)) {
            return $this->builderNameCache[$modelClassName] = EloquentBuilder::class;
        }

        $classNames = $returnType->getObjectClassNames();

        if (count($classNames) === 1) {
            return $this->builderNameCache[$modelClassName] = $classNames[0];
        }

        return $this->builderNameCache[$modelClassName] = $returnType->describe(VerbosityLevel::value());
    }

    /**
     * Mirrors Model::resolveCustomBuilderClass(). It instantiates the attribute, so the argument may also be named.
     * Since Laravel 13.31, it also reads the model's traits, then each parent class and its traits, nearest first.
     */
    private function findUseEloquentBuilderClassName(ClassReflection $modelReflection): string|null
    {
        $classReflections = [$modelReflection];

        // @phpstan-ignore if.alwaysTrue, if.alwaysFalse
        if (version_compare(LARAVEL_VERSION, '13.31.0', '>=')) {
            $classReflections = [];

            foreach ([$modelReflection, ...$modelReflection->getParents()] as $classReflection) {
                $classReflections[] = $classReflection;

                foreach ($classReflection->getTraits() as $traitReflection) {
                    $classReflections[] = $traitReflection;
                }
            }
        }

        foreach ($classReflections as $classReflection) {
            $attributes = $classReflection->getNativeReflection()->getAttributes('Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder'); //@phpstan-ignore argument.type (Attribute class might not exist)

            if ($attributes === []) {
                continue;
            }

            $arguments = $attributes[0]->getArgumentsExpressions();
            $expr      = $arguments[0] ?? $arguments['builderClass'] ?? null;

            if ($expr instanceof ClassConstFetch && $expr->class instanceof Name) {
                return $expr->class->toString();
            }

            if ($expr instanceof String_) {
                return ltrim($expr->value, '\\');
            }

            return null;
        }

        return null;
    }

    public function determineBuilderClass(string $modelClassName, Type $modelType): Type|null
    {
        try {
            $builderClassName = $this->determineBuilderName($modelClassName);
        } catch (InvalidArgumentException) {
            return null;
        } catch (MissingMethodFromReflectionException) {
            $builderClassName = EloquentBuilder::class;
        }

        return $this->getBuilderType($builderClassName, $modelType);
    }
}

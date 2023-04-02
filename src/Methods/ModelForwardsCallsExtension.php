<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;

use function array_map;

final class ModelForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var BuilderHelper */
    private $builderHelper;

    /** @var ReflectionProvider */
    private $reflectionProvider;

    /** @var EloquentBuilderForwardsCallsExtension */
    private $eloquentBuilderForwardsCallsExtension;

    /** @var array<string, MethodReflection> */
    private $cache = [];

    public function __construct(BuilderHelper $builderHelper, ReflectionProvider $reflectionProvider, EloquentBuilderForwardsCallsExtension $eloquentBuilderForwardsCallsExtension)
    {
        $this->builderHelper = $builderHelper;
        $this->reflectionProvider = $reflectionProvider;
        $this->eloquentBuilderForwardsCallsExtension = $eloquentBuilderForwardsCallsExtension;
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($classReflection->getCacheKey().'-'.$methodName, $this->cache)) {
            return true;
        }

        $methodReflection = $this->findMethod($classReflection, $methodName);

        if ($methodReflection !== null) {
            $this->cache[$classReflection->getCacheKey().'-'.$methodName] = $methodReflection;

            return true;
        }

        return false;
    }

    /**
     * @param  ClassReflection  $classReflection
     * @param  string  $methodName
     * @return MethodReflection
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getCacheKey().'-'.$methodName];
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    private function findMethod(ClassReflection $classReflection, string $methodName): ?MethodReflection
    {
        if ($classReflection->getName() !== Model::class && ! $classReflection->isSubclassOf(Model::class)) {
            return null;
        }

        $builderName = $this->builderHelper->determineBuilderName($classReflection->getName());

        if (in_array($methodName, ['increment', 'decrement'], true)) {
            $methodReflection = $classReflection->getNativeMethod($methodName);

            return new class($classReflection, $methodName, $methodReflection) implements MethodReflection
            {
                /** @var ClassReflection */
                private $classReflection;

                /** @var string */
                private $methodName;

                /** @var MethodReflection */
                private $methodReflection;

                public function __construct(ClassReflection $classReflection, string $methodName, MethodReflection $methodReflection)
                {
                    $this->classReflection = $classReflection;
                    $this->methodName = $methodName;
                    $this->methodReflection = $methodReflection;
                }

                public function getDeclaringClass(): ClassReflection
                {
                    return $this->classReflection;
                }

                public function isStatic(): bool
                {
                    return false;
                }

                public function isPrivate(): bool
                {
                    return false;
                }

                public function isPublic(): bool
                {
                    return true;
                }

                public function getDocComment(): ?string
                {
                    return null;
                }

                public function getName(): string
                {
                    return $this->methodName;
                }

                public function getPrototype(): ClassMemberReflection
                {
                    return $this;
                }

                public function getVariants(): array
                {
                    return $this->methodReflection->getVariants();
                }

                public function isDeprecated(): TrinaryLogic
                {
                    return TrinaryLogic::createNo();
                }

                public function getDeprecatedDescription(): ?string
                {
                    return null;
                }

                public function isFinal(): TrinaryLogic
                {
                    return TrinaryLogic::createNo();
                }

                public function isInternal(): TrinaryLogic
                {
                    return TrinaryLogic::createNo();
                }

                public function getThrowType(): ?Type
                {
                    return null;
                }

                public function hasSideEffects(): TrinaryLogic
                {
                    return TrinaryLogic::createYes();
                }
            };
        }

        $builderReflection = $this->reflectionProvider->getClass($builderName)->withTypes([new ObjectType($classReflection->getName())]);
        $genericBuilderAndModelType = new GenericObjectType($builderName, [new ObjectType($classReflection->getName())]);

        if ($builderReflection->hasNativeMethod($methodName)) {
            $reflection = $builderReflection->getNativeMethod($methodName);

            $parametersAcceptor = ParametersAcceptorSelector::selectSingle($this->transformStaticParameters($reflection, $genericBuilderAndModelType));

            $returnType = TypeTraverser::map($parametersAcceptor->getReturnType(), static function (Type $type, callable $traverse) use ($genericBuilderAndModelType) {
                if ($type instanceof TypeWithClassName && $type->getClassName() === Builder::class) {
                    return $genericBuilderAndModelType;
                }

                return $traverse($type);
            });

            return new EloquentBuilderMethodReflection(
                $methodName, $builderReflection,
                $reflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic()
            );
        }

        if ($this->eloquentBuilderForwardsCallsExtension->hasMethod($builderReflection, $methodName)) {
            return $this->eloquentBuilderForwardsCallsExtension->getMethod($builderReflection, $methodName);
        }

        return null;
    }

    /**
     * @return ParametersAcceptor[]
     */
    private function transformStaticParameters(MethodReflection $method, GenericObjectType $builder): array
    {
        return array_map(function (ParametersAcceptor $acceptor) use ($builder): ParametersAcceptor {
            return new FunctionVariant($acceptor->getTemplateTypeMap(), $acceptor->getResolvedTemplateTypeMap(), array_map(function (
                ParameterReflection $parameter) use ($builder): ParameterReflection {
                return new DummyParameter(
                    $parameter->getName(),
                    $this->transformStaticType($parameter->getType(), $builder),
                    $parameter->isOptional(),
                    $parameter->passedByReference(),
                    $parameter->isVariadic(),
                    $parameter->getDefaultValue()
                );
            }, $acceptor->getParameters()), $acceptor->isVariadic(), $this->transformStaticType($acceptor->getReturnType(), $builder));
        }, $method->getVariants());
    }

    private function transformStaticType(Type $type, GenericObjectType $builder): Type
    {
        return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($builder): Type {
            if ($type instanceof StaticType) {
                return $builder;
            }

            return $traverse($type);
        });
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;

use function array_key_exists;
use function array_map;
use function in_array;

final class ModelForwardsCallsExtension implements MethodsClassReflectionExtension
{
    /** @var array<string, MethodReflection> */
    private array $cache = [];

    public function __construct(private BuilderHelper $builderHelper, private ReflectionProvider $reflectionProvider, private EloquentBuilderForwardsCallsExtension $eloquentBuilderForwardsCallsExtension)
    {
    }

    /**
     * @throws MissingMethodFromReflectionException
     * @throws ShouldNotHappenException
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (array_key_exists($classReflection->getCacheKey() . '-' . $methodName, $this->cache)) {
            return true;
        }

        $methodReflection = $this->findMethod($classReflection, $methodName);

        if ($methodReflection !== null) {
            $this->cache[$classReflection->getCacheKey() . '-' . $methodName] = $methodReflection;

            return true;
        }

        return false;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->cache[$classReflection->getCacheKey() . '-' . $methodName];
    }

    /**
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    private function findMethod(ClassReflection $classReflection, string $methodName): MethodReflection|null
    {
        if (! $classReflection->is(Model::class)) {
            return null;
        }

        if (in_array($methodName, ['increment', 'decrement'], true)) {
            return $this->counterMethodReflection($classReflection, $methodName);
        }

        $builderName       = $this->builderHelper->determineBuilderName($classReflection->getName());
        $builderReflection = $this->reflectionProvider->getClass($builderName)->withTypes([new ObjectType($classReflection->getName())]);
        $builderType       = $builderReflection->isGeneric()
            ? new GenericObjectType($builderName, [new ObjectType($classReflection->getName())])
            : new ObjectType($builderName);

        if ($builderReflection->hasNativeMethod($methodName)) {
            $reflection = $builderReflection->getNativeMethod($methodName);

            $parametersAcceptor = $this->transformStaticParameters($reflection, $builderType);

            $returnType = TypeTraverser::map($parametersAcceptor->getReturnType(), static function (Type $type, callable $traverse) use ($builderType) {
                if ($type instanceof TypeWithClassName && $type->getClassName() === Builder::class) {
                    return $builderType;
                }

                return $traverse($type);
            });

            return new EloquentBuilderMethodReflection(
                $methodName,
                $builderReflection,
                $parametersAcceptor->getParameters(),
                $returnType,
                $parametersAcceptor->isVariadic(),
            );
        }

        if (! $this->eloquentBuilderForwardsCallsExtension->hasMethod($builderReflection, $methodName)) {
            return null;
        }

        $reflection = $this->eloquentBuilderForwardsCallsExtension->getMethod($builderReflection, $methodName);

        if (! $reflection instanceof EloquentBuilderMethodReflection) {
            return $reflection;
        }

        $returnType = $reflection->getVariants()[0]->getReturnType();

        if (! $returnType instanceof ThisType) {
            return $reflection;
        }

        return new EloquentBuilderMethodReflection(
            $reflection->getName(),
            $reflection->getDeclaringClass(),
            $reflection->getVariants()[0]->getParameters(),
            $returnType->getStaticObjectType(),
            $reflection->getVariants()[0]->isVariadic(),
        );
    }

    private function transformStaticParameters(MethodReflection $method, ObjectType $builder): ParametersAcceptor
    {
        $acceptor = $method->getVariants()[0];

        return new FunctionVariant($acceptor->getTemplateTypeMap(), $acceptor->getResolvedTemplateTypeMap(), array_map(function (
            ParameterReflection $parameter,
        ) use ($builder): ParameterReflection {
            return new DummyParameter(
                $parameter->getName(),
                $this->transformStaticType($parameter->getType(), $builder),
                $parameter->isOptional(),
                $parameter->passedByReference(),
                $parameter->isVariadic(),
                $parameter->getDefaultValue(),
            );
        }, $acceptor->getParameters()), $acceptor->isVariadic(), $this->transformStaticType($acceptor->getReturnType(), $builder));
    }

    private function transformStaticType(Type $type, ObjectType $builder): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($builder): Type {
            if ($type instanceof StaticType) {
                return $builder;
            }

            return $traverse($type);
        });
    }

    private function counterMethodReflection(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $methodReflection = $classReflection->getNativeMethod($methodName);

        return new class ($classReflection, $methodName, $methodReflection) implements MethodReflection {
            public function __construct(private ClassReflection $classReflection, private string $methodName, private MethodReflection $methodReflection)
            {
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

            public function getDocComment(): string|null
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

            /** @return ParametersAcceptor[] */
            public function getVariants(): array
            {
                return $this->methodReflection->getVariants();
            }

            public function isDeprecated(): TrinaryLogic
            {
                return TrinaryLogic::createNo();
            }

            public function getDeprecatedDescription(): string|null
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

            public function getThrowType(): Type|null
            {
                return null;
            }

            public function hasSideEffects(): TrinaryLogic
            {
                return TrinaryLogic::createYes();
            }
        };
    }
}

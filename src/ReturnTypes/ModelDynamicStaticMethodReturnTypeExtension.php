<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class ModelDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    /** @var BuilderHelper */
    private $builderHelper;
    /** @var ReflectionProvider */
    private $reflectionProvider;

    /**
     * @param  BuilderHelper  $builderHelper
     */
    public function __construct(BuilderHelper $builderHelper, ReflectionProvider $reflectionProvider)
    {
        $this->builderHelper = $builderHelper;
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Model::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $name = $methodReflection->getName();

        if ($name === '__construct') {
            return false;
        }

        // Another extension handles this case
        if (Str::startsWith($name, 'find')) {
            return false;
        }

        if (in_array($name, ['get', 'hydrate', 'fromQuery'], true)) {
            return true;
        }

        if (! $methodReflection->getDeclaringClass()->hasMethod($name)) {
            return false;
        }

        $method = $methodReflection->getDeclaringClass()->getMethod($methodReflection->getName(), new OutOfClassScope());

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        return $this->doesClassesContainTypeOrSubType(
            $returnType->getReferencedClasses(),
            [EloquentBuilder::class, QueryBuilder::class, Collection::class]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $method = $methodReflection->getDeclaringClass()
            ->getMethod($methodReflection->getName(), $scope);

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if ($this->doesClassesContainTypeOrSubType($returnType->getReferencedClasses(), [EloquentBuilder::class])
            && $methodCall->class instanceof \PhpParser\Node\Name
        ) {
            $returnType = new GenericObjectType(
                $this->builderHelper->determineBuilderName($scope->resolveName($methodCall->class)),
                [new ObjectType($scope->resolveName($methodCall->class))]
            );
        }

        if (
            $methodCall->class instanceof \PhpParser\Node\Name
            && $this->doesClassesContainTypeOrSubType($returnType->getReferencedClasses(), [Collection::class])
            && in_array($methodReflection->getName(), ['get', 'hydrate', 'fromQuery', 'all', 'findMany'], true)
        ) {
            $collectionClassName = $this->builderHelper->determineCollectionClassName($scope->resolveName($methodCall->class));

            return new GenericObjectType($collectionClassName, [new IntegerType(), new ObjectType($scope->resolveName($methodCall->class))]);
        }

        return $returnType;
    }

    /**
     * @param array<string> $givenClassNames
     * @param array<class-string> $neededClassNames
     * @return bool
     */
    private function doesClassesContainTypeOrSubType(array $givenClassNames, array $neededClassNames): bool
    {
        foreach ($givenClassNames as $givenClassName) {
            $givenClassReflection = $this->reflectionProvider->getClass($givenClassName);

            foreach ($neededClassNames as $neededClassName) {
                if ($givenClassName === $neededClassName) {
                    return true;
                }

                if ($givenClassReflection->isSubclassOf($neededClassName)) {
                    return true;
                }
            }
        }

        return false;
    }
}

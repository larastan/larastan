<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Types\Factory\ModelFactoryType;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

use function array_map;
use function class_exists;
use function count;

final class ModelFactoryDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'factory';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $class = $methodCall->class;

        $calledOnType = $class instanceof Name
           ? new ObjectType($scope->resolveName($class))
           : $scope->getType($class);

        if (count($methodCall->getArgs()) === 0) {
            $isSingleModel = TrinaryLogic::createYes();
        } else {
            $argType = $scope->getType($methodCall->getArgs()[0]->value);

            $numericTypes = [
                new IntegerType(),
                new FloatType(),
                new IntersectionType([
                    new StringType(),
                    new AccessoryNumericStringType(),
                ]),
            ];

            $isSingleModel = (new UnionType($numericTypes))->isSuperTypeOf($argType)->negate();
        }

        return TypeCombinator::union(...array_map(
            function (ClassReflection $classReflection) use ($scope, $isSingleModel) {
                $factoryReflection = $this->getFactoryReflection($classReflection, $scope);

                if ($factoryReflection === null) {
                    return new ErrorType();
                }

                return new ModelFactoryType($factoryReflection->getName(), null, $factoryReflection, $isSingleModel);
            },
            $calledOnType->getObjectClassReflections(),
        ));
    }

    private function getFactoryReflection(
        ClassReflection $modelReflection,
        Scope $scope,
    ): ClassReflection|null {
        $factoryReflection = $this->getFactoryFromNewFactoryMethod($modelReflection, $scope);

        if ($factoryReflection !== null) {
            return $factoryReflection;
        }

        /** @phpstan-ignore argument.type (guaranteed to be model class-string) */
        $factoryClass = Factory::resolveFactoryName($modelReflection->getName());

        if (class_exists($factoryClass)) {
            return $this->reflectionProvider->getClass($factoryClass);
        }

        return null;
    }

    private function getFactoryFromNewFactoryMethod(
        ClassReflection $modelReflection,
        Scope $scope,
    ): ClassReflection|null {
        if (! $modelReflection->hasMethod('newFactory')) {
            return null;
        }

        $factoryReflections = $modelReflection->getMethod('newFactory', $scope)
            ->getVariants()[0]
            ->getReturnType()
            ->getObjectClassReflections();

        if (count($factoryReflections) !== 1) {
            return null;
        }

        foreach ($factoryReflections as $factoryReflection) {
            if (
                $factoryReflection->isSubclassOf(Factory::class)
                && ! $factoryReflection->isAbstract()
            ) {
                return $factoryReflection;
            }
        }

        return null;
    }
}

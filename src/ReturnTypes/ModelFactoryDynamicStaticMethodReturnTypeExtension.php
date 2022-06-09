<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class ModelFactoryDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
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
        Scope $scope
    ): Type {
        $class = $methodCall->class;

        if (! $class instanceof Name) {
            return new ErrorType();
        }

        $factoryName = Factory::resolveFactoryName(ltrim($class->toCodeString(), '\\')); // @phpstan-ignore-line

        if (class_exists($factoryName)) {
            return new ObjectType($factoryName);
        }

        $modelName = basename(str_replace('\\', '/', $class->toCodeString()));

        if (! class_exists('Database\\Factories\\'.$modelName.'Factory')) {
            return new ErrorType();
        }

        return new ObjectType('Database\\Factories\\'.$modelName.'Factory');
    }
}

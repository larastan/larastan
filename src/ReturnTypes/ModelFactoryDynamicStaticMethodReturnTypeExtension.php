<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
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
        if ($methodReflection->getName() !== 'factory') {
            return false;
        }

        // Class only available on Laravel 8
        if (! class_exists('\Illuminate\Database\Eloquent\Factories\Factory')) {
            return false;
        }

        $modelName = $methodReflection->getDeclaringClass()->getNativeReflection()->getShortName();

        return class_exists('Database\\Factories\\'.$modelName.'Factory');
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $modelName = $methodReflection->getDeclaringClass()->getNativeReflection()->getShortName();

        return new ObjectType('Database\\Factories\\'.$modelName.'Factory');
    }
}

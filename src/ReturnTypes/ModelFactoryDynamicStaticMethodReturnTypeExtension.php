<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Types\Factory\ModelFactoryType;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

use function basename;
use function class_exists;
use function count;
use function ltrim;
use function str_replace;

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
        Scope $scope,
    ): Type {
        $class = $methodCall->class;

        if (! $class instanceof Name) {
            return new ErrorType();
        }

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

        $factoryName = Factory::resolveFactoryName(ltrim($class->toCodeString(), '\\')); // @phpstan-ignore-line

        if (class_exists($factoryName)) {
            return new ModelFactoryType($factoryName, null, null, $isSingleModel);
        }

        $modelName = basename(str_replace('\\', '/', $class->toCodeString()));

        if (! class_exists('Database\\Factories\\' . $modelName . 'Factory')) {
            return new ErrorType();
        }

        return new ModelFactoryType('Database\\Factories\\' . $modelName . 'Factory', null, null, $isSingleModel);
    }
}

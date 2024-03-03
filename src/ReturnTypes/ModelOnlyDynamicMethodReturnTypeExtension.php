<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

use function array_push;
use function array_reduce;
use function count;

final class ModelOnlyDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'only';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $args = $methodCall->getArgs();

        if (count($args) < 1) {
            return new ErrorType();
        }

        $keys = [];

        foreach ($args as $arg) {
            $type = $scope->getType($arg->value);

            $stringsArray   = [];
            $stringsArray[] = $type->getConstantStrings();

            foreach ($type->getArrays() as $array) {
                $stringsArray[] = $array->getItemType()->getConstantStrings();
            }

            foreach ($stringsArray as $strings) {
                if (count($strings) > 0) {
                    array_push($keys, ...$strings);
                    continue 2;
                }
            }

            // encountered an argument that does not resolve to a constant string
            return new ArrayType(new StringType(), new MixedType());
        }

        $model = $scope->getType($methodCall->var);

        $array = array_reduce($keys, static function ($array, $key) use ($model, $scope) {
            $name = $key->getValue();

            $valueType = $model->hasProperty($name)->yes()
                ? $model->getProperty($name, $scope)->getReadableType()
                : new NullType();

            $array->setOffsetValueType($key, $valueType);

            return $array;
        }, ConstantArrayTypeBuilder::createEmpty());

        return $array->getArray();
    }
}

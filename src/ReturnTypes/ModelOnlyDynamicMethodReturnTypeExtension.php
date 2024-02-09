<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

use function array_map;
use function array_filter;
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
        Scope $scope
    ): Type {
        $fields = $methodCall->getArgs();

        if (count($fields) < 1) {
            return new ErrorType();
        }

        if ($fields[0]->value instanceof Array_) {
            $fields = $fields[0]->value->items;
        }

        $fields = array_map(static fn ($f) => $f->value, array_filter($fields));

        $model = $scope->getType($methodCall->var);
        $array = ConstantArrayTypeBuilder::createEmpty();

        foreach ($fields as $field) {
            if (! $field instanceof String_) {
                return new ArrayType(new StringType(), new MixedType());
            }

            $name = $field->value;

            $valueType = $model->hasProperty($name)->yes()
                ? $model->getProperty($name, $scope)->getReadableType()
                : new NullType();

            $array->setOffsetValueType(new ConstantStringType($name), $valueType);
        }

        return $array->getArray();
    }
}

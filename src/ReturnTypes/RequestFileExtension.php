<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function count;

/** @internal */
final class RequestFileExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Request::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'file';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $uploadedFileType      = new ObjectType(UploadedFile::class);
        $uploadedFileArrayType = new ArrayType(new IntegerType(), $uploadedFileType);

        if (count($methodCall->getArgs()) === 0) {
            return new ArrayType(new IntegerType(), $uploadedFileType);
        }

        if (count($methodCall->getArgs()) === 1) {
            return new BenevolentUnionType([$uploadedFileArrayType, $uploadedFileType, new NullType()]);
        }

        return new BenevolentUnionType([$uploadedFileArrayType, $uploadedFileType, $scope->getType($methodCall->getArgs()[1]->value)]);
    }
}

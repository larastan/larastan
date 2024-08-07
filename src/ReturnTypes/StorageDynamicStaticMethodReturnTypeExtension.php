<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function in_array;

class StorageDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    use HasContainer;

    public function getClass(): string
    {
        return Storage::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['disk', 'drive', 'cloud'], strict: true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        return new ObjectType(FilesystemAdapter::class);
    }
}

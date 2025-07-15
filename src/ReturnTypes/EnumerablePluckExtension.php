<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Enumerable;
use Larastan\Larastan\Support\PluckHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EnumerablePluckExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private PluckHelper $pluckHelper)
    {
    }

    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'pluck';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $calledOnType    = $scope->getType($methodCall->var);
        $collectionClass = null;
        $valueArg        = $methodCall->getArg('value', 0);
        $keyArg          = $methodCall->getArg('key', 1);

        if ($valueArg === null) {
            return null;
        }

        if ((new ObjectType(EloquentCollection::class))->isSuperTypeOf($calledOnType)->no()) {
            $collectionClass = $calledOnType->getObjectClassNames()[0] ?? null;
        }

        $from = $calledOnType->getTemplateType(Enumerable::class, 'TValue');

        return $this->pluckHelper->getCollectionType($from, $valueArg, $keyArg, $scope, $collectionClass);
    }
}

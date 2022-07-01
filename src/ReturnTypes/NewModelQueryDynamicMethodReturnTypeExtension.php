<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

class NewModelQueryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private BuilderHelper $builderHelper)
    {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'newQuery', 'newModelQuery', 'newQueryWithoutRelationships',
            'newQueryWithoutScopes', 'newQueryWithoutScope', 'newQueryForRestoration',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        $calledOnType = $scope->getType($methodCall->var);

        if (! $calledOnType instanceof TypeWithClassName) {
            return null;
        }

        $builderName = $this->builderHelper->determineBuilderName($calledOnType->getClassName());

        return new GenericObjectType($builderName, [new ObjectType($calledOnType->getClassName())]);
    }
}

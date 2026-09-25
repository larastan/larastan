<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Types\BuilderOf\BuilderOfType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;

use function in_array;

/** @internal */
final class NewModelQueryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
            'newQuery',
            'newModelQuery',
            'newQueryWithoutRelationships',
            'newQueryWithoutScopes',
            'newQueryWithoutScope',
            'newQueryForRestoration',
        ], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

        if ((new ObjectType(EloquentBuilder::class))->isSuperTypeOf($returnType)->yes()) {
            return null;
        }

        if (! $this->builderHelper->isBuilderReturnType($returnType)) {
            return null;
        }

        $calledOnType = $scope->getType($methodCall->var);

        if (! (new ObjectType(Model::class))->isSuperTypeOf($calledOnType)->yes()) {
            return null;
        }

        return new BuilderOfType(
            $calledOnType instanceof ThisType ? new StaticType($calledOnType->getClassReflection()) : $calledOnType,
            $this->builderHelper,
        );
    }
}

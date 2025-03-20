<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function in_array;

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
        $calledOnType = $scope->getType($methodCall->var);

        $classReflections = $calledOnType->getObjectClassReflections();

        if ($classReflections === []) {
            return null;
        }

        $types = [];

        foreach ($classReflections as $classReflection) {
            if (! $classReflection->is(Model::class)) {
                continue;
            }

            $builderName = $this->builderHelper->determineBuilderName($classReflection->getName());

            $types[] = new GenericObjectType($builderName, [new ObjectType($classReflection->getName())]);
        }

        if ($types === []) {
            return null;
        }

        return TypeCombinator::union(...$types);
    }
}

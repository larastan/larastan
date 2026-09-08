<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
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

            // Eloquent declares these methods as `@return Builder<static>`. On a model that is
            // not final, `static` is strictly narrower than the class it resolves to, and
            // Builder is invariant in its model parameter, so handing back the class name makes
            // `Builder<static>` unsatisfiable for anything that declares it. Keep `static` when
            // the call was made on it; on a final model the two are the same type and the plain
            // object type reads better in error messages.
            $modelType = ! $classReflection->isFinal()
                && $calledOnType instanceof StaticType
                && $calledOnType->getClassName() === $classReflection->getName()
                    ? new StaticType($classReflection)
                    : new ObjectType($classReflection->getName());

            $types[] = $this->builderHelper->getBuilderType($builderName, $modelType);
        }

        if ($types === []) {
            return null;
        }

        return TypeCombinator::union(...$types);
    }
}

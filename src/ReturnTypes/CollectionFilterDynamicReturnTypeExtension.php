<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function assert;
use function count;
use function is_string;

class CollectionFilterDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'filter';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $calledOnType = $scope->getType($methodCall->var);

        if ($calledOnType->getObjectClassReflections() === [] || ! $calledOnType->getObjectClassReflections()[0]->isGeneric()) {
            return null;
        }

        $keyType   = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TKey');
        $valueType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TValue');

        if ($keyType === null || $valueType === null) {
            return null;
        }

        if (count($methodCall->getArgs()) < 1) {
            $nonFalseyTypes = TypeCombinator::removeFalsey($valueType);

            return new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $nonFalseyTypes]);
        }

        $callbackArg = $methodCall->getArgs()[0]->value;

        $var  = null;
        $expr = null;

        if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
            $statement = $callbackArg->stmts[0];
            if ($statement instanceof Return_ && $statement->expr !== null) {
                $var  = $callbackArg->params[0]->var;
                $expr = $statement->expr;
            }
        } elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
            $var  = $callbackArg->params[0]->var;
            $expr = $callbackArg->expr;
        }

        if ($var !== null && $expr !== null) {
            if (! $var instanceof Variable || ! is_string($var->name)) {
                throw new ShouldNotHappenException();
            }

            $itemVariableName = $var->name;

            /** @phpstan-ignore phpstanApi.class (not covered by BC promise) */
            assert($scope instanceof MutatingScope);

            /** @phpstan-ignore phpstanApi.method (not covered by BC promise) */
            $scope     = $scope->assignExpression(new Variable($itemVariableName), $valueType);
            $scope     = $scope->filterByTruthyValue($expr);
            $valueType = $scope->getVariableType($itemVariableName);
        }

        return new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $valueType]);
    }
}

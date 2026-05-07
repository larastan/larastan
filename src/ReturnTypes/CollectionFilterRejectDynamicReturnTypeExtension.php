<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Enumerable;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function assert;
use function count;
use function in_array;
use function is_string;

class CollectionFilterRejectDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const SUPPORTED_METHOD_NAMES = ['filter', 'reject', 'where'];

    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), self::SUPPORTED_METHOD_NAMES, true);
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

        if ($keyType instanceof TemplateType) {
            $keyType = $keyType->getBound();
        }

        if ($valueType instanceof TemplateType) {
            $valueType = $valueType->getBound();
        }

        $methodName = $methodReflection->getName();
        assert(in_array($methodName, self::SUPPORTED_METHOD_NAMES), 'proven in isMethodSupported');

        if (count($methodCall->getArgs()) < 1) {
            $modifiedType = match ($methodName) {
                'filter' => TypeCombinator::removeFalsey($valueType),
                'reject' => TypeCombinator::removeTruthy($valueType),
                'where' => null,
            };

            return $modifiedType !== null
                ? new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $modifiedType])
                : null;
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
        } elseif ($methodName === 'where') {
            // where call may have non-callable as first parameter; we don't support narrowing in this case
            return null;
        }

        if ($var !== null && $expr !== null) {
            if (! $var instanceof Variable || ! is_string($var->name)) {
                throw new ShouldNotHappenException();
            }

            $itemVariableName = $var->name;

            $node = new Variable($itemVariableName);
            // @phpstan-ignore-next-line
            $scope = $scope->assignExpression($node, $valueType, $valueType);
            $scope = match ($methodName) {
                'filter', 'where' => $scope->filterByTruthyValue($expr),
                'reject' => $scope->filterByFalseyValue($expr),
            };
            $valueType = $scope->getVariableType($itemVariableName);
        }

        return new GenericObjectType($calledOnType->getObjectClassNames()[0], [$keyType, $valueType]);
    }
}

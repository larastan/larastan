<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Larastan\Larastan\Support\ConfigParser;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;

use function count;

class ConfigFunctionDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function __construct(private ConfigParser $configParser)
    {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        if ($this->configParser->getConfigPaths() === []) {
            return false;
        }

        return $functionReflection->getName() === 'config';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type|null {
        $args     = $functionCall->getArgs();
        $argCount = count($args);

        if ($argCount === 0) {
            return null;
        }

        $firstArgType = $scope->getType($args[0]->value);

        $defaultArgType = null;

        if ($argCount > 1) {
            $defaultArgType = $scope->getType($args[1]->value);
        }

        $constantStrings = $firstArgType->getConstantStrings();

        $returnTypes = [];

        if ($defaultArgType !== null) {
            $returnTypes[] = TypeTraverser::map($defaultArgType, static function (Type $type, callable $traverse) use ($scope): Type {
                if ($type->isConstantScalarValue()->no() && $type->isCallable()->yes()) {
                    return $type->getCallableParametersAcceptors($scope)[0]->getReturnType();
                }

                return $traverse($type);
            })->generalize(GeneralizePrecision::lessSpecific());
        }

        $returnTypes += $this->configParser->getTypes($constantStrings, $scope);

        return $returnTypes === [] ? null : TypeCombinator::union(...$returnTypes);
    }
}

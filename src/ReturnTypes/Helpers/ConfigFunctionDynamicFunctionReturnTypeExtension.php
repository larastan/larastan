<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Larastan\Larastan\Internal\ConfigHelper;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

class ConfigFunctionDynamicFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    public function __construct(private ConfigHelper $configHelper)
    {
    }

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        if (! $this->configHelper->hasConfigPaths()) {
            return false;
        }

        return $functionReflection->getName() === 'config';
    }

    /** @param Scope&DependencyEmitter $scope */
    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type|null {
        $args = $functionCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $returnTypes = $this->configHelper->getReturnTypes($args, $scope);

        return $returnTypes === [] ? null : TypeCombinator::union(...$returnTypes);
    }
}

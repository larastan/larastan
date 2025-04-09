<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Config\Repository;
use Larastan\Larastan\Support\ConfigParser;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;

use function count;

class ConfigGetDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ConfigParser $configParser)
    {
    }

    public function getClass(): string
    {
        return Repository::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if ($this->configParser->getConfigPaths() === []) {
            return false;
        }

        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $firstArgType = $scope->getType($args[0]->value);

        $defaultArgType = null;

        if (count($args) > 1) {
            $defaultArgType = $scope->getType($args[1]->value);
        }

        $constantStrings = $firstArgType->getConstantStrings();
        $returnTypes     = [];

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

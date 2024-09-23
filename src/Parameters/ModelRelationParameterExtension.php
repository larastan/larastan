<?php

declare(strict_types=1);

namespace Larastan\Larastan\Parameters;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\Type;

final class ModelRelationParameterExtension implements StaticMethodParameterClosureTypeExtension
{
    public function __construct(private RelationClosureHelper $relationClosureHelper)
    {
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $this->relationClosureHelper->isMethodSupported($methodReflection, $parameter);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        return $this->relationClosureHelper->getTypeFromMethodCall($methodReflection, $methodCall, $parameter, $scope);
    }
}

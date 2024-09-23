<?php

declare(strict_types=1);

namespace Larastan\Larastan\Parameters;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\Type;

final class EloquentBuilderRelationParameterExtension implements MethodParameterClosureTypeExtension
{
    public function __construct(private RelationClosureHelper $relationClosureHelper)
    {
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $this->relationClosureHelper->isMethodSupported($methodReflection, $parameter);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        return $this->relationClosureHelper->getTypeFromMethodCall($methodReflection, $methodCall, $parameter, $scope);
    }
}

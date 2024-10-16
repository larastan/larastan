<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Facades\Config;
use Larastan\Larastan\Support\ConfigHelper;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function in_array;

/** @internal */
final class ConfigDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(private ConfigHelper $configHelper)
    {
    }

    public function getClass(): string
    {
        return Config::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['get', 'getMany', 'array', 'all'], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type|null {
        return $this->configHelper->determineConfigType($methodReflection, $methodCall, $scope);
    }
}

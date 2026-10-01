<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Larastan\Larastan\Internal\ConfigHelper;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\DependencyEmitter;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

class ConfigFacadeCollectionDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
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
        if (! $this->configHelper->hasConfigPaths()) {
            return false;
        }

        return $methodReflection->getName() === 'collection';
    }

    /** @param Scope&DependencyEmitter $scope */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $returnTypes = $this->configHelper->getReturnTypes($args, $scope);

        $configType = TypeCombinator::union(...$returnTypes);

        if ($returnTypes === [] || ! $configType->isArray()->yes()) {
            return new ErrorType();
        }

        return new GenericObjectType(Collection::class, [
            $configType->getIterableKeyType(),
            $configType->getIterableValueType(),
        ]);
    }
}

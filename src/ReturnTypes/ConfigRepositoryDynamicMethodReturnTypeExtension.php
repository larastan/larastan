<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Larastan\Larastan\Internal\ConfigHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

class ConfigRepositoryDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ConfigHelper $configHelper)
    {
    }

    public function getClass(): string
    {
        return Repository::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        if (! $this->configHelper->hasConfigPaths()) {
            return false;
        }

        return in_array($methodReflection->getName(), ['get', 'collection'], true);
    }

    /** @param Scope&DependencyTracker $scope */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $returnTypes = $this->configHelper->getReturnTypes($args, $scope);

        if ($methodReflection->getName() === 'get') {
            return $returnTypes === [] ? null : TypeCombinator::union(...$returnTypes);
        }

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

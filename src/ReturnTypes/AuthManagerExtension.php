<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Auth\AuthManager;
use Larastan\Larastan\Concerns;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function count;

final class AuthManagerExtension implements DynamicMethodReturnTypeExtension
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    public function getClass(): string
    {
        return AuthManager::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'user';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $config     = $this->getContainer()->get('config');
        $authModels = [];

        if ($config !== null) {
            $authModels = $this->getAuthModels($config);
        }

        if (count($authModels) === 0) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return TypeCombinator::addNull(
            TypeCombinator::union(...array_map(
                static fn (string $authModel): Type => new ObjectType($authModel),
                $authModels,
            )),
        );
    }
}

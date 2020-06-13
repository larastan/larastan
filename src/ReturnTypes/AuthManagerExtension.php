<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Auth\AuthManager;
use Illuminate\Config\Repository as ConfigRepository;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class AuthManagerExtension implements DynamicMethodReturnTypeExtension
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
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
        Scope $scope
    ): Type {
        $config = $this->getContainer()
            ->get('config');

        if ($authModel = $this->getAuthModel($config)) {
            return TypeCombinator::addNull(new ObjectType($authModel));
        }

        return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
    }

    /**
    * Returns the default auth model from config.
    *
    * @return string|null
    */
    private function getAuthModel(ConfigRepository $config)
    {
        if ($guard = $config->get('auth.defaults.guard')) {
            if ($provider = $config->get('auth.guards.'.$guard.'.provider')) {
                if ($authModel = $config->get('auth.providers.'.$provider.'.model')) {
                    return $authModel;
                }
            }
        }

        return null;
    }
}

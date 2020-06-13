<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Auth;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class AuthExtension implements DynamicStaticMethodReturnTypeExtension
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Auth::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
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
        if (
            ! ($guard = $config->get('auth.defaults.guard')) ||
            ! ($provider = $config->get('auth.guards.'.$guard.'.provider')) ||
            ! ($authModel = $config->get('auth.providers.'.$provider.'.model'))
        ) {
            return null;
        }

        return $authModel;
    }
}

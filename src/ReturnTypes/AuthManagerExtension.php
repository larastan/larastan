<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\ReturnTypes;

use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Illuminate\Auth\AuthManager;
use PHPStan\Type\TypeCombinator;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;

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

        if ($userModel = $config->get('auth.providers.users.model')) {
            return TypeCombinator::addNull(new ObjectType($userModel));
        }

        return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
    }
}

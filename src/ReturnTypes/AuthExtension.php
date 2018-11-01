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
use PHPStan\Type\TypeCombinator;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\StaticCall;
use Illuminate\Support\Facades\Auth;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

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

        $userModel = $config->get('auth.providers.users.model');

        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        if ($userModel) {
            return TypeCombinator::union($returnType, new ObjectType($userModel));
        }
        return $returnType;

    }
}

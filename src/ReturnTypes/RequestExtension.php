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
use PHPStan\Type\MixedType;
use Illuminate\Http\Request;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;

/**
 * @internal
 */
final class RequestExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Request::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'input';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        return new MixedType;
    }
}

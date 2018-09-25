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

namespace NunoMaduro\Larastan\ReturnTypes\Helpers;

use function count;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;

/**
 * @internal
 */
final class RequestExtension implements DynamicFunctionReturnTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'request';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        // No arguments, it returns a request object from the container
        if (count($functionCall->args) === 0) {
            return new ObjectType(\Illuminate\Http\Request::class);
        }

        if (isset($functionCall->args[0]->value) && $functionCall->args[0]->value instanceof Array_) {
            return new ArrayType(new MixedType(), new MixedType());
        }

        return new MixedType();
    }
}

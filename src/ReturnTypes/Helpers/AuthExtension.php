<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes\Helpers;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class AuthExtension implements DynamicFunctionReturnTypeExtension
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'auth';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope
    ): Type {
        if (count($functionCall->getArgs()) < 1) {
            /** @var ?object $class */
            $class = $this->resolve(\Illuminate\Contracts\Auth\Factory::class);

            if ($class === null) {
                return new ObjectType(\Illuminate\Contracts\Auth\Factory::class);
            }

            return new ObjectType(get_class($class));
        }

        return TypeCombinator::intersect(new ObjectType(Guard::class), new ObjectType(StatefulGuard::class));
    }
}

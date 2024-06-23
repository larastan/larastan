<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes\Helpers;

use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Larastan\Larastan\Concerns;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_key_exists;
use function count;
use function is_string;

/** @internal */
final class AuthExtension implements DynamicFunctionReturnTypeExtension
{
    use Concerns\HasContainer;

    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'auth';
    }

    public function getTypeFromFunctionCall(
        FunctionReflection $functionReflection,
        FuncCall $functionCall,
        Scope $scope,
    ): Type {
        if (count($functionCall->getArgs()) < 1) {
            /** @var ?object $class */
            $class = $this->resolve(Factory::class);

            if ($class === null) {
                return new ObjectType(Factory::class);
            }

            return new ObjectType($class::class);
        }

        return $this->getTypeFromStaticGuardCall($functionCall, $scope);
    }

    public function getTypeFromStaticGuardCall(
        FuncCall $methodCall,
        Scope $scope,
    ): Type {
        $defaultReturnType = TypeCombinator::intersect(new ObjectType(Guard::class), new ObjectType(StatefulGuard::class));

        $config = $this->getContainer()->get('config');
        if ($config === null) {
            return $defaultReturnType;
        }

        /** @var array<string, mixed> $guards */
        $guards = $config->get('auth.guards');
        /** @var string $defaultGuard */
        $defaultGuard = $config->get('auth.defaults.guard');

        if (count($methodCall->getArgs()) === 0) {
            if (! array_key_exists($defaultGuard, $guards)) {
                return $defaultReturnType;
            }

            return $this->findTypeFromGuardDriver($guards[$defaultGuard]['driver']) ?? $defaultReturnType;
        }

        $argType    = $scope->getType($methodCall->getArgs()[0]->value);
        $argStrings = $argType->getConstantStrings();

        if (count($argStrings) !== 1) {
            return $defaultReturnType;
        }

        $driver = $config->get('auth.guards.' . $argStrings[0]->getValue() . '.driver', null);
        if (! is_string($driver)) {
            return $defaultReturnType;
        }

        return $this->findTypeFromGuardDriver($driver) ?? $defaultReturnType;
    }

    private function findTypeFromGuardDriver(string $driver): Type|null
    {
        return match ($driver) {
            'session' => new ObjectType(SessionGuard::class),
            'token' => new ObjectType(TokenGuard::class),
            'passport' => new ObjectType(RequestGuard::class),
            default => null,
        };
    }
}

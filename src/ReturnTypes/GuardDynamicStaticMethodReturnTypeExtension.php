<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_key_exists;
use function count;

class GuardDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    use HasContainer;

    public function getClass(): string
    {
        return Auth::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'guard';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $defaultReturnType = TypeCombinator::intersect(new ObjectType(Guard::class), new ObjectType(StatefulGuard::class));

        $config = $this->getContainer()->get('config');
        if ($config === null) {
            return $defaultReturnType;
        }

        /** @var string $defaultGuard */
        $defaultGuard = $config->get('auth.defaults.guard');

        if (count($methodCall->getArgs()) === 0) {
            /** @var array<string, mixed> $guards */
            $guards = $config->get('auth.guards');

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

        return $this->findTypeFromGuardDriver($argStrings[0]->getValue()) ?? $defaultReturnType;
    }

    private function findTypeFromGuardDriver(string $driver): Type|null
    {
        return match ($driver) {
            'session' => new ObjectType('Illuminate\Auth\SessionGuard'),
            'token' => new ObjectType(TokenGuard::class),
            'passport' => new ObjectType(RequestGuard::class),
            default => null,
        };
    }
}

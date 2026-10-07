<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

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

use function count;
use function is_string;

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

        if (count($methodCall->getArgs()) === 0) {
            $guardName = $config->get('auth.defaults.guard');

            if (! is_string($guardName)) {
                return $defaultReturnType;
            }
        } else {
            $argType    = $scope->getType($methodCall->getArgs()[0]->value);
            $argStrings = $argType->getConstantStrings();

            if (count($argStrings) !== 1) {
                return $defaultReturnType;
            }

            $guardName = $argStrings[0]->getValue();
        }

        $driver = $config->get('auth.guards')[$guardName]['driver'] ?? null;

        if (! is_string($driver)) {
            return $defaultReturnType;
        }

        return $this->findTypeFromGuardDriver($driver) ?? $defaultReturnType;
    }

    private function findTypeFromGuardDriver(string $driver): Type|null
    {
        return match ($driver) {
            'session' => new ObjectType('Illuminate\Auth\SessionGuard'),
            'token' => new ObjectType(TokenGuard::class),
            default => null,
        };
    }
}

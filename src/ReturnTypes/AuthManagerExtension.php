<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Exception;
use Illuminate\Auth\AuthManager;
use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Auth\TokenGuard;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Larastan\Larastan\Concerns;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_key_exists;
use function array_map;
use function count;
use function in_array;
use function is_string;

final class AuthManagerExtension implements DynamicMethodReturnTypeExtension
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    public function getClass(): string
    {
        return AuthManager::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['user', 'authenticate', 'guard'], true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
               return match ($methodReflection->getName()) {
            'authenticate' => $this->getTypeFromAuthenticateCall($methodReflection),
            'user' => $this->getTypeFromUserCall($methodReflection),
            'guard' => $this->getTypeFromGuardCall($methodCall, $scope),
            default => throw new Exception('Unhandled static method return type extension' . $methodReflection->getName())
               };
    }

    public function getTypeFromAuthenticateCall(
        MethodReflection $methodReflection,
    ): Type {
        $config     = $this->getContainer()->get('config');
        $authModels = [];

        if ($config !== null) {
            $authModels = $this->getAuthModels($config);
        }

        if (count($authModels) === 0) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        $type = TypeCombinator::union(...array_map(
            static fn (string $authModel): Type => new ObjectType($authModel),
            $authModels,
        ));

        return $type;
    }

    public function getTypeFromUserCall(
        MethodReflection $methodReflection,
    ): Type {
        return TypeCombinator::addNull($this->getTypeFromAuthenticateCall($methodReflection));
    }

    public function getTypeFromGuardCall(
        MethodCall $methodCall,
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

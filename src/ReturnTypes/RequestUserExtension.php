<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Http\Request;
use Larastan\Larastan\Concerns\HasContainer;
use Larastan\Larastan\Concerns\LoadsAuthModel;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function count;

/** @internal */
final class RequestUserExtension implements DynamicMethodReturnTypeExtension
{
    use HasContainer;
    use LoadsAuthModel;

    public function getClass(): string
    {
        return Request::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'user';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type {
        $config     = $this->getContainer()->get('config');
        $authModels = [];

        if ($config !== null) {
            $guard      = $this->getGuardFromMethodCall($scope, $methodCall);
            $authModels = $this->getAuthModels($config, $guard);
        }

        if (count($authModels) === 0) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return TypeCombinator::addNull(
            TypeCombinator::union(...array_map(
                static fn (string $authModel): Type => new ObjectType($authModel),
                $authModels,
            )),
        );
    }

    private function getGuardFromMethodCall(Scope $scope, MethodCall $methodCall): string|null
    {
        $args = $methodCall->getArgs();

        if (count($args) !== 1) {
            return null;
        }

        $guardType       = $scope->getType($args[0]->value);
        $constantStrings = $guardType->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}

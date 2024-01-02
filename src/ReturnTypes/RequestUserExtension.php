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

/**
 * @internal
 */
final class RequestUserExtension implements DynamicMethodReturnTypeExtension
{
    use HasContainer;
    use LoadsAuthModel;

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
        return $methodReflection->getName() === 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $config = $this->getContainer()->get('config');
        $authModel = null;

        if ($config !== null) {
            $guard = $this->getGuardFromMethodCall($scope, $methodCall);
            $authModel = $this->getAuthModel($config, $guard);
        }

        if ($authModel === null) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return TypeCombinator::addNull(new ObjectType($authModel));
    }

    private function getGuardFromMethodCall(Scope $scope, MethodCall $methodCall): ?string
    {
        $args = $methodCall->getArgs();

        if (count($args) !== 1) {
            return null;
        }

        $guardType = $scope->getType($args[0]->value);
        $constantStrings = $guardType->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}

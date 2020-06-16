<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Contracts\Auth\Guard;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class GuardExtension implements DynamicMethodReturnTypeExtension
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Guard::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'user';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $config = $this->getContainer()
            ->get('config');

        $authModel = $this->getDefaultAuthModel($config);

        if ($authModel === null) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return TypeCombinator::addNull(new ObjectType($authModel));
    }
}

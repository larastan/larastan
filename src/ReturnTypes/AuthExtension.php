<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Facades\Auth;
use Larastan\Larastan\Concerns;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function count;
use function in_array;

/** @internal */
final class AuthExtension implements DynamicStaticMethodReturnTypeExtension
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    public function getClass(): string
    {
        return Auth::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['user', 'authenticate']);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
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
        if ($methodReflection->getName() === 'user') {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }
}

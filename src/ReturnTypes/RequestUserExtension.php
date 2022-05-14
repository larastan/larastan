<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Http\Request;
use NunoMaduro\Larastan\Concerns\HasContainer;
use NunoMaduro\Larastan\Concerns\LoadsAuthModel;
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
        $authModels = [];

        if ($config !== null) {
            $authModels = $this->getAuthModels($config);
        }

        if (empty($authModels)) {
            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        return TypeCombinator::addNull(
            TypeCombinator::union(
                ...array_map(
                    fn (string $authModel): Type => new ObjectType($authModel),
                    $authModels,
                ),
            ),
        );
    }
}

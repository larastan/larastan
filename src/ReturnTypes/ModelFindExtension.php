<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\ReturnTypes;

use PHPStan\Type\Type;
use Illuminate\Support\Str;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use PhpParser\Node\Expr\Array_;
use PHPStan\Type\TypeCombinator;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Expr\StaticCall;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

/**
 * @internal
 */
final class ModelFindExtension implements DynamicStaticMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Model::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return Str::startsWith($methodReflection->getName(), 'find');
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $modelName = $methodReflection->getDeclaringClass()->getName();

        if ($methodCall->args[0]->value instanceof Array_) {
            return TypeCombinator::remove($methodReflection->getVariants()[0]->getReturnType(), new ObjectType($modelName));
        }

        if ($methodCall->args[0]->value instanceof LNumber) {
            return TypeCombinator::remove(
                $methodReflection->getVariants()[0]->getReturnType(),
                new IterableType(new IntegerType(), new ObjectType($modelName))
            );
        }

        return $methodReflection->getVariants()[0]->getReturnType();
    }
}

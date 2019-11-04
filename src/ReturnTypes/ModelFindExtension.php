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
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\IterableType;
use PhpParser\Node\Expr\Array_;
use PHPStan\Type\TypeCombinator;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Scalar\LNumber;
use PHPStan\Type\IntersectionType;
use PhpParser\Node\Expr\StaticCall;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use Illuminate\Database\Query\Builder as QueryBuilder;
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
        $methodName = $methodReflection->getName();

        if (! Str::startsWith($methodName, 'find')) {
            return false;
        }

        if (! $this->getBroker()->getClass(Builder::class)->hasNativeMethod($methodName) &&
            ! $this->getBroker()->getClass(QueryBuilder::class)->hasNativeMethod($methodName)) {
            return false;
        }

        return true;
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
        $returnType = $methodReflection->getVariants()[0]->getReturnType();

        if ($methodCall->args[0]->value instanceof Array_) {
            return TypeCombinator::remove($returnType, new ObjectType($modelName));
        }

        if ($methodCall->args[0]->value instanceof LNumber) {
            return TypeCombinator::remove(
                $returnType,
                new IntersectionType([new ObjectType(Collection::class), new IterableType(new MixedType(), new ObjectType($modelName))])
            );
        }

        return $returnType;
    }
}

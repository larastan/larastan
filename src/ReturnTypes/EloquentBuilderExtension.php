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
use PhpParser\Node\Name;
use Illuminate\Support\Str;
use PHPStan\Analyser\Scope;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PhpParser\Node\Expr\New_;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IterableType;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\Variable;
use PHPStan\Type\IntersectionType;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use NunoMaduro\Larastan\Methods\ModelTypeHelper;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use NunoMaduro\Larastan\Methods\ModelForwardsCallsExtension;

final class EloquentBuilderExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    public function getClass(): string
    {
        return Builder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        // Don't handle dynamic wheres
        if (Str::startsWith($methodReflection->getName(), 'where')) {
            return false;
        }

        if ($methodReflection instanceof DummyMethodReflection) {
            return true;
        }

        if (in_array($methodReflection->getName(), array_merge(ModelForwardsCallsExtension::MODEL_CREATION_METHODS, ModelForwardsCallsExtension::MODEL_RETRIEVAL_METHODS), true)) {
            return true;
        }

        return $methodReflection->getName() === 'get';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        while ($methodCall->var instanceof MethodCall) {
            $methodCall = $methodCall->var;
        }

        $modelType = new MixedType();

        if ($methodCall->var instanceof StaticCall || $methodCall->var instanceof New_) {
            if ($methodCall->var->class instanceof Variable) {
                $modelType = $scope->getType($methodCall->var->class);
            } elseif ($methodCall->var->class instanceof FullyQualified) {
                $modelType = new ObjectType($methodCall->var->class->toCodeString());
            } elseif ($methodCall->var->class instanceof Name) {
                $modelType = new ObjectType($scope->resolveName($methodCall->var->class));
            }
        } elseif ($methodCall->var instanceof Variable || $methodCall->var instanceof PropertyFetch) {
            /** @var ObjectType $modelType */
            $modelType = $scope->getType($methodCall->var);
        }

        if ($methodReflection instanceof DummyMethodReflection && $modelType instanceof ObjectType) {
            $scopeMethodName = 'scope'.ucfirst($methodReflection->getName());
            $modelReflection = $this->getBroker()->getClass($modelType->getClassName());

            if ($modelReflection->hasNativeMethod($scopeMethodName)) {
                return new ObjectType(Builder::class);
            }
        }

        if ($modelType instanceof ObjectType && in_array($methodReflection->getName(), array_merge(ModelForwardsCallsExtension::MODEL_CREATION_METHODS, ModelForwardsCallsExtension::MODEL_RETRIEVAL_METHODS), true)) {
            return ModelTypeHelper::replaceStaticTypeWithModel($methodReflection->getVariants()[0]->getReturnType(), $modelType->getClassName());
        }

        // 'get' method return type
        return new IntersectionType([
            new IterableType(new IntegerType(), $modelType), new ObjectType(Collection::class),
        ]);
    }
}

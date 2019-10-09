<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use PhpParser\Node\Name\FullyQualified;
use PHPStan\Type\Type;
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
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;

final class EloquentBuilderExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    public function getClass(): string
    {
        return Builder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
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

        $modelName = new MixedType();

        if ($methodCall->var instanceof StaticCall || $methodCall->var instanceof New_) {
            /** @var FullyQualified $fullQualifiedClass */
            $fullQualifiedClass = $methodCall->var->class;
            $modelName = new ObjectType($fullQualifiedClass->toCodeString());
        } elseif ($methodCall->var instanceof Variable) {
            $modelName = $scope->getType($methodCall->var);
        }

        return new IntersectionType([
            new IterableType(new IntegerType(), $modelName), new ObjectType(Collection::class)
        ]);
    }
}

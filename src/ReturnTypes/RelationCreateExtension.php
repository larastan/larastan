<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Types\RelationType;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class RelationCreateExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Relation::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'get' ||
               $methodReflection->getName() === 'make' ||
               $methodReflection->getName() === 'create' ||
               $methodReflection->getName() === 'getEager' ||
               $methodReflection->getName() === 'getResults';
    }

    /**
     * @throws ShouldNotHappenException
     * @throws BindingResolutionException
     * @throws ClassNotFoundException
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        /** @var MethodCall $relationMethodCall */
        $relationMethodCall = $methodCall->var;

        if (! $relationMethodCall instanceof MethodCall) {
            return new MixedType(true);
        }

        $relationType = $scope->getType($methodCall->var);

        if (! $relationType instanceof RelationType) {
            return new MixedType(true);
        }

        if (in_array($methodReflection->getName(), ['get', 'getResults'], true)) {
            return new GenericObjectType(Collection::class, [new ObjectType($relationType->getRelatedModel())]);
        }

        return new ObjectType($relationType->getRelatedModel());
    }
}

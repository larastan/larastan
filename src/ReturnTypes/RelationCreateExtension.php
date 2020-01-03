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

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Broker\Broker;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;

class RelationCreateExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    use HasContainer;

    /** @var Broker */
    private $broker;

    public function getClass(): string
    {
        return Relation::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'create';
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

        /** @var Identifier $relationMethodIdentifier */
        $relationMethodIdentifier = $relationMethodCall->name;

        $propertyName = $relationMethodIdentifier->name;

        /** @var ObjectType|ThisType $modelType */
        $modelType = $scope->getType($relationMethodCall->var);

        if ($modelType instanceof ObjectType) {
            $modelClass = $modelType->getClassName();
        } elseif ($modelType instanceof ThisType) {
            $modelClass = $modelType->getBaseClass();
        } else {
            return new MixedType(true);
        }

        $modelReflection = $this->broker->getClass($modelClass);
        if (! $modelReflection->isSubclassOf(Model::class)) {
            throw new ShouldNotHappenException();
        }

        if (! $modelReflection->hasMethod($propertyName)) {
            return new MixedType(true);
        }

        $relatedModel = get_class($this->getContainer()->make($modelClass)->{$propertyName}()->getRelated());

        return new ObjectType($relatedModel);
    }

    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }
}

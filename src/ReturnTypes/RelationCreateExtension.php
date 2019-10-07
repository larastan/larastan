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
use PHPStan\Broker\Broker;
use PHPStan\Analyser\Scope;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PhpParser\Node\Identifier;
use PhpParser\Node\Expr\Variable;
use Illuminate\Support\Collection;
use PHPStan\Type\IntersectionType;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPStan\Reflection\Annotations\AnnotationsPropertiesClassReflectionExtension;

class RelationCreateExtension implements DynamicMethodReturnTypeExtension, BrokerAwareExtension
{
    /**
     * @var Broker
     */
    private $broker;

    /**
     * @var AnnotationsPropertiesClassReflectionExtension
     */
    private $annotationsPropertiesClassReflectionExtension;

    public function __construct(AnnotationsPropertiesClassReflectionExtension $annotationsPropertiesClassReflectionExtension)
    {
        $this->annotationsPropertiesClassReflectionExtension = $annotationsPropertiesClassReflectionExtension;
    }

    public function getClass(): string
    {
        return Relation::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'create';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $returnType = new MixedType(true);

        try {
            [$context, $relationName] = $this->getContextFromMethodCall($methodCall);

            $callingClass = $this->determineCallingClass($scope, $context);

            if ($this->annotationsPropertiesClassReflectionExtension->hasProperty($callingClass, $relationName)) {
                $returnType = $this->annotationsPropertiesClassReflectionExtension->getProperty($callingClass, $relationName)->getType();

                if ($returnType instanceof IntersectionType) {
                    return $this->determineReturnTypeFromIntersection($returnType);
                }

                if ($returnType instanceof ObjectType && $this->isModel($returnType->getClassName())) {
                    return $returnType;
                }
            }
        } catch (ClassNotFoundException $e) {
            // Silently fail...
        }

        return $returnType;
    }

    public function setBroker(Broker $broker): void
    {
        $this->broker = $broker;
    }

    /**
     * @param Scope $scope
     * @param string $context
     * @return ClassReflection
     * @throws ClassNotFoundException
     */
    private function determineCallingClass(Scope $scope, string $context) : ClassReflection
    {
        /** @var string $className */
        $className = current(array_filter($scope->debug(), function (string $key) use ($context) {
            return mb_strpos($key, $context) !== false;
        }, ARRAY_FILTER_USE_KEY));

        if (mb_strpos($className, '$this') !== false) {
            $className = $this->stripThisFromClassName($className);
        }

        return $this->broker->getClass($className);
    }

    /**
     * @param string $className
     * @return string
     */
    private function stripThisFromClassName(string $className) : string
    {
        preg_match('/\$this\((.*?)\)/', $className, $out);

        return $out[1];
    }

    /**
     * @param IntersectionType $returnType
     * @return Type
     * @throws ClassNotFoundException
     */
    private function determineReturnTypeFromIntersection(IntersectionType $returnType) : Type
    {
        [$collectionClass, $model] = $returnType->getReferencedClasses();

        if ($collectionClass === Collection::class || $this->broker->getClass($collectionClass)->isSubclassOf(Collection::class)) {
            if (! $this->isModel($model)) {
                return new MixedType(true);
            }

            return new ObjectType($model);
        }

        return new MixedType(true);
    }

    /**
     * @param string $className
     * @return bool
     * @throws ClassNotFoundException
     */
    private function isModel(string $className) : bool
    {
        return $this->broker->getClass($className)->isSubclassOf(Model::class);
    }

    /**
     * @param MethodCall $methodCall
     * @return array
     */
    private function getContextFromMethodCall(MethodCall $methodCall) : array
    {
        /** @var MethodCall $methodCallNode */
        $methodCallNode = $methodCall->var;

        /** @var Variable|PropertyFetch $methodCallVariable */
        $methodCallVariable = $methodCallNode->var;

        if (! $methodCallVariable instanceof Variable) {
            /** @var Variable $methodCallVariable */
            $methodCallVariable = $methodCallVariable->var;
        }

        /** @var string $context */
        $context = $methodCallVariable->name;

        /** @var Identifier $methodCallIdentifier */
        $methodCallIdentifier = $methodCallNode->name;

        /** @var string $relationName */
        $relationName = $methodCallIdentifier->name;

        return [$context, $relationName];
    }
}

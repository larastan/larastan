<?php

declare(strict_types=1);

namespace Larastan\Larastan\ClosureTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Types\BuilderOf\BuilderOfType;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;

use function in_array;

final class RelationshipQueryCallbackExtension implements MethodParameterClosureTypeExtension, StaticMethodParameterClosureTypeExtension
{
    public function __construct(private BuilderHelper $builderHelper)
    {
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        if (! $methodReflection->getDeclaringClass()->is(Builder::class)) {
            return false;
        }

        return match ($parameter->getName()) {
            'callback' => in_array($methodReflection->getName(), ['with', 'withWhereHas', 'hasMorph', 'doesntHaveMorph', 'whereHasMorph', 'orWhereHasMorph', 'whereDoesntHaveMorph', 'orWhereDoesntHaveMorph'], true),
            'column' => in_array($methodReflection->getName(), ['withWhereRelation', 'whereMorphRelation', 'orWhereMorphRelation', 'whereMorphDoesntHaveRelation', 'orWhereMorphDoesntHaveRelation'], true),
            default => false,
        };
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $this->isMethodSupported($methodReflection, $parameter);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, ParameterReflection $parameter, Scope $scope): Type|null
    {
        return $this->getCallbackType($methodReflection, $methodCall, $parameter, $scope);
    }

    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, ParameterReflection $parameter, Scope $scope): Type|null
    {
        return $this->getCallbackType($methodReflection, $methodCall, $parameter, $scope);
    }

    private function getCallbackType(MethodReflection $methodReflection, MethodCall|StaticCall $call, ParameterReflection $parameter, Scope $scope): Type|null
    {
        $arguments = [];

        foreach ($call->getArgs() as $position => $argument) {
            $arguments[$argument->name?->toString() ?? $position] = $argument->value;
        }

        $method   = $methodReflection->getName();
        $relation = $arguments[$method === 'with' ? 'relations' : 'relation'] ?? $arguments[0] ?? null;
        $types    = $arguments['types'] ?? $arguments[1] ?? null;
        $model    = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModel');

        if ($relation === null || $model === null) {
            return null;
        }

        if (in_array($method, ['with', 'withWhereHas', 'withWhereRelation'], true)) {
            $queryType = $this->getEagerCallbackType($model, $scope->getType($relation), $method !== 'with');

            if ($queryType === null) {
                return null;
            }
        } else {
            if ($types === null) {
                return null;
            }

            $fallback = [];

            foreach (TypeUtils::flattenTypes($scope->getType($relation)) as $relationType) {
                $fallback[] = $relationType->isString()->yes()
                    ? (new BuilderOfType($model, $this->builderHelper, $relationType))->resolve()
                    : (new BuilderOfType($relationType->getTemplateType(Relation::class, 'TRelatedModel'), $this->builderHelper))->resolve();
            }

            $fallback = TypeCombinator::union(...$fallback);
            $builders = [];

            foreach (TypeUtils::flattenTypes($scope->getType($types)) as $type) {
                if ($type->isArray()->yes()) {
                    $type = $type->getIterableValueType();
                }

                foreach (TypeUtils::flattenTypes($type) as $target) {
                    $objectType = $target->getClassStringObjectType();
                    $builders[] = $target->isClassString()->yes() && (new ObjectType(Model::class))->isSuperTypeOf($objectType)->yes()
                        ? (new BuilderOfType($objectType, $this->builderHelper))->resolve()
                        : $fallback;
                }
            }

            $queryType = TypeCombinator::union(...$builders);
        }

        return TypeTraverser::map($parameter->getType(), static function (Type $type, callable $traverse) use ($queryType): Type {
            if ($type instanceof ClosureType) {
                return $type->traverse(static fn (Type $parameterType): Type => TypeCombinator::union(new ObjectType(Builder::class), new ObjectType(Relation::class))->isSuperTypeOf($parameterType)->yes() ? $queryType : $parameterType);
            }

            return $traverse($type);
        });
    }

    private function getEagerCallbackType(Type $modelType, Type $relationNames, bool $includeBuilder): Type|null
    {
        if (! $relationNames->isConstantScalarValue()->yes()) {
            return null;
        }

        $builderType  = new BuilderOfType($modelType, $this->builderHelper, $relationNames);
        $relationType = $builderType->resolveRelationType();

        if ($relationType === null) {
            return null;
        }

        // Eager loading constructs the relation on a fresh model instance.
        $relationType = TypeTraverser::map($relationType, static fn (Type $type, callable $traverse): Type => $type instanceof StaticType ? $type->getStaticObjectType() : $traverse($type));

        return $includeBuilder ? TypeCombinator::union($builderType->resolve(), $relationType) : $relationType;
    }
}

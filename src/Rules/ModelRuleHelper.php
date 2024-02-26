<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

final class ModelRuleHelper
{
    public function findModelReflectionFromType(Type $type): ClassReflection|null
    {
        if (
            ! (new ObjectType(Builder::class))->isSuperTypeOf($type)->yes() &&
            ! (new ObjectType(EloquentBuilder::class))->isSuperTypeOf($type)->yes() &&
            ! (new ObjectType(Relation::class))->isSuperTypeOf($type)->yes() &&
            ! (new ObjectType(Model::class))->isSuperTypeOf($type)->yes()
        ) {
            return null;
        }

        // We expect it to be generic builder or relation class with model type inside
        if ((! $type instanceof GenericObjectType) && (new ObjectType(Model::class))->isSuperTypeOf($type)->no()) {
            return null;
        }

        if ($type instanceof GenericObjectType) {
            $modelType = $type->getTypes()[0];
        } else {
            $modelType = $type;
        }

        $modelType = TypeCombinator::removeNull($modelType);

        $classReflections = $modelType->getObjectClassReflections();

        if (count($classReflections) !== 1) {
            return null;
        }

        $modelReflection = $classReflections[0];

        if ($modelReflection->getName() === Model::class) {
            return null;
        }

        if ($modelReflection->isAbstract()) {
            return null;
        }

        return $modelReflection;
    }
}

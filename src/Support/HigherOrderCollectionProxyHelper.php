<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HigherOrderCollectionProxy;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type;

class HigherOrderCollectionProxyHelper
{
    /**
     * @phpstan-param 'method'|'property' $propertyOrMethod
     */
    public static function hasPropertyOrMethod(ClassReflection $classReflection, string $name, string $propertyOrMethod): bool
    {
        if ($classReflection->getName() !== HigherOrderCollectionProxy::class) {
            return false;
        }

        $activeTemplateTypeMap = $classReflection->getActiveTemplateTypeMap();

        if ($activeTemplateTypeMap->count() !== 2) {
            return false;
        }

        $methodType = $activeTemplateTypeMap->getType('T');
        /** @var ?Type\ObjectType $modelType */
        $modelType = $activeTemplateTypeMap->getType('TModel');

        if (($methodType === null) || ($modelType === null)) {
            return false;
        }

        if (! (new Type\ObjectType(Model::class))->isSuperTypeOf($modelType)->yes()) {
            return false;
        }

        if ($propertyOrMethod === 'method') {
            return $modelType->hasMethod($name)->yes();
        }

        return $modelType->hasProperty($name)->yes();
    }

    public static function determineReturnType(string $name, Type\Type $modelType, Type\Type $methodOrPropertyReturnType): Type\Type
    {
        switch ($name) {
            case 'average':
            case 'avg':
                $returnType = new Type\FloatType();
                break;
            case 'contains':
            case 'every':
            case 'some':
                $returnType = new Type\BooleanType();
                break;
            case 'each':
            case 'filter':
            case 'keyBy':
            case 'reject':
            case 'skipUntil':
            case 'skipWhile':
            case 'sortBy':
            case 'sortByDesc':
            case 'takeUntil':
            case 'takeWhile':
            case 'unique':
                $returnType = new Type\Generic\GenericObjectType(Collection::class, [$modelType]);
                break;
            case 'first':
                $returnType = Type\TypeCombinator::addNull($modelType);
                break;
            case 'flatMap':
                $returnType = new Type\Generic\GenericObjectType(\Illuminate\Support\Collection::class, [new Type\IntegerType(), new Type\MixedType()]);
                break;
            case 'groupBy':
            case 'partition':
                $returnType = new Type\Generic\GenericObjectType(Collection::class, [
                    new Type\Generic\GenericObjectType(Collection::class, [$modelType]),
                ]);
                break;
            case 'map':
                $returnType = new Type\Generic\GenericObjectType(\Illuminate\Support\Collection::class, [
                    new Type\IntegerType(),
                    $methodOrPropertyReturnType,
                ]);
                break;
            case 'max':
            case 'min':
                $returnType = $methodOrPropertyReturnType;
                break;
            case 'sum':
                if ($methodOrPropertyReturnType->accepts(new Type\IntegerType(), true)->yes()) {
                    $returnType = new Type\IntegerType();
                } else {
                    $returnType = new Type\ErrorType();
                }

                break;
            default:
                $returnType = new Type\ErrorType();
                break;
        }

        return $returnType;
    }
}

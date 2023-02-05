<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Support;

use Illuminate\Support\Collection as SupportCollection;
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

        if ($activeTemplateTypeMap->count() !== 3) {
            return false;
        }

        $methodType = $activeTemplateTypeMap->getType('T');
        $valueType = $activeTemplateTypeMap->getType('TValue');

        if (($methodType === null) || ($valueType === null)) {
            return false;
        }

        $constants = $methodType->getConstantStrings();

        if (count($constants) !== 1) {
            return false;
        }

        if (! $valueType->canCallMethods()->yes()) {
            return false;
        }

        if ($propertyOrMethod === 'method') {
            return $valueType->hasMethod($name)->yes();
        }

        return $valueType->hasProperty($name)->yes();
    }

    public static function determineReturnType(string $name, Type\Type $valueType, Type\Type $methodOrPropertyReturnType, string $collectionType): Type\Type
    {
        $types = [new Type\IntegerType(), $valueType];

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
            case 'reject':
            case 'skipUntil':
            case 'skipWhile':
            case 'sortBy':
            case 'sortByDesc':
            case 'takeUntil':
            case 'takeWhile':
            case 'unique':
                $returnType = new Type\Generic\GenericObjectType($collectionType, $types);
                break;
            case 'keyBy':
                $returnType = new Type\Generic\GenericObjectType($collectionType, [new Type\BenevolentUnionType([new Type\IntegerType(), new Type\StringType()]), $valueType]);
                break;
            case 'first':
                $returnType = Type\TypeCombinator::addNull($valueType);
                break;
            case 'flatMap':
                $returnType = new Type\Generic\GenericObjectType(SupportCollection::class, [new Type\IntegerType(), new Type\MixedType()]);
                break;
            case 'groupBy':
            case 'partition':
                $innerTypes = [
                    new Type\IntegerType(),
                    new Type\Generic\GenericObjectType($collectionType, $types),
                ];

                $returnType = new Type\Generic\GenericObjectType($collectionType, $innerTypes);
                break;
            case 'map':
                $returnType = new Type\Generic\GenericObjectType(SupportCollection::class, [
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

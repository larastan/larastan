<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\HigherOrderCollectionProxy;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;

use function count;

class HigherOrderCollectionProxyHelper
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    /** @phpstan-param 'method'|'property' $propertyOrMethod */
    public function hasPropertyOrMethod(ClassReflection $classReflection, string $name, string $propertyOrMethod): bool
    {
        if ($classReflection->getName() !== HigherOrderCollectionProxy::class) {
            return false;
        }

        $activeTemplateTypeMap = $classReflection->getActiveTemplateTypeMap();

        if ($activeTemplateTypeMap->count() !== 3) {
            return false;
        }

        $methodType = $activeTemplateTypeMap->getType('T');
        $valueType  = $activeTemplateTypeMap->getType('TValue');

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

        return $valueType->hasInstanceProperty($name)->yes();
    }

    public function determineReturnType(string $name, Type\Type $valueType, Type\Type $methodOrPropertyReturnType, string $collectionType): Type\Type
    {
        $integerType = new Type\IntegerType();

        return match ($name) {
            'average', 'avg' => new Type\FloatType(),
            'contains', 'every', 'some' => new Type\BooleanType(),
            'each', 'filter', 'reject', 'skipUntil', 'skipWhile', 'sortBy', 'sortByDesc', 'takeUntil', 'takeWhile', 'unique' => $this->getCollectionType($collectionType, $integerType, $valueType),
            'keyBy' => $this->getCollectionType($collectionType, new Type\BenevolentUnionType([$integerType, new Type\StringType()]), $valueType),
            'first' => Type\TypeCombinator::addNull($valueType),
            'flatMap' => $this->getCollectionType(SupportCollection::class, $integerType, new Type\MixedType()),
            'groupBy', 'partition' => $this->getCollectionType($collectionType, $integerType, $this->getCollectionType($collectionType, $integerType, $valueType)),
            'map' => $this->getCollectionType(
                SupportCollection::class,
                new BenevolentUnionType([new IntegerType(), new StringType()]),
                $methodOrPropertyReturnType,
            ),
            'max', 'min' => $methodOrPropertyReturnType,
            'sum' => $methodOrPropertyReturnType->accepts(new Type\IntegerType(), true)->yes() ? new Type\IntegerType() : new Type\ErrorType(),
            default => new Type\ErrorType(),
        };
    }

    private function getCollectionType(string $collectionClassName, Type\Type $keyType, Type\Type $valueType): Type\Type
    {
        $collectionReflection = $this->reflectionProvider->getClass($collectionClassName);

        if ($collectionReflection->isGeneric()) {
            $typeMap = $collectionReflection->getActiveTemplateTypeMap();

            // Specifies key and value
            if ($typeMap->count() === 2) {
                return new GenericObjectType($collectionClassName, [$keyType, $valueType]);
            }

            // Specifies only value
            if (($typeMap->count() === 1) && $typeMap->hasType('TModel')) {
                return new GenericObjectType($collectionClassName, [$valueType]);
            }
        }

        // Not generic. So return the type as is
        return new ObjectType($collectionClassName);
    }
}

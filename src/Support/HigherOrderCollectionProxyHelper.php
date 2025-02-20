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

        return $valueType->hasProperty($name)->yes();
    }

    public function determineReturnType(string $name, Type\Type $valueType, Type\Type $methodOrPropertyReturnType, string $collectionType): Type\Type
    {
        $integerType = new Type\IntegerType();

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
                $returnType = $this->getCollectionType($collectionType, $integerType, $valueType);
                break;
            case 'keyBy':
                $returnType = $this->getCollectionType($collectionType, new Type\BenevolentUnionType([$integerType, new Type\StringType()]), $valueType);
                break;
            case 'first':
                $returnType = Type\TypeCombinator::addNull($valueType);
                break;
            case 'flatMap':
                $returnType = $this->getCollectionType(SupportCollection::class, $integerType, new Type\MixedType());
                break;
            case 'groupBy':
            case 'partition':
                $returnType = $this->getCollectionType($collectionType, $integerType, $this->getCollectionType($collectionType, $integerType, $valueType));
                break;
            case 'map':
                $returnType = $this->getCollectionType(
                    SupportCollection::class,
                    new BenevolentUnionType([new IntegerType(), new StringType()]),
                    $methodOrPropertyReturnType,
                );
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

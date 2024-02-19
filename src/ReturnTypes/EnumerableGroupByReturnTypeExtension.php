<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Enumerable;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PhpParser\Node\Expr\MethodCall;

use function array_reverse;
use function count;

class EnumerableGroupByReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'groupBy';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): ?Type {
        if (count($methodCall->getArgs()) < 1) {
            return null;
        }

        $calledOnType = $scope->getType($methodCall->var);
        $objectClassReflections = $calledOnType->getObjectClassReflections();

        if (!isset($objectClassReflections[0])) {
            return null;
        }

        $groupByType = $scope->getType($methodCall->getArgs()[0]->value);
        $propertyTypes = [];

        $collectionName = $objectClassReflections[0]->getName();

        $valueType = new MixedType();

        if ($objectClassReflections[0]->isGeneric()) {
            $tValueType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TValue');

            if ($tValueType !== null) {
                $valueType = $tValueType;
            }
        }

        if ($groupByType->isString()->yes()) {
            $propertyTypes[] = $groupByType;
        } elseif ($groupByType->isConstantArray()->yes()) {
            if ($groupByType->isIterableAtLeastOnce()->no()) {
                return $this->unknownKeysType($collectionName, $valueType);
            }

            $valuesArray = $groupByType->getConstantArrays()[0]->getValueTypes();

            foreach ($valuesArray as $valuesType) {
                $propertyTypes[] = $valuesType;
            }
        } else {
            return $this->unknownKeysType($collectionName, new MixedType());
        }


        $innerKeyType = new IntegerType();

        if (count($methodCall->getArgs()) >= 2) {
            $preserveType = $scope->getType($methodCall->getArgs()[1]->value);
            $tKeyType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TKey');

            if ($tKeyType === null) {
                $innerKeyType = new BenevolentUnionType([new IntegerType(), new StringType()]);
            } elseif ($preserveType->isTrue()->yes()) {
                $innerKeyType = $tKeyType;
            } elseif ($preserveType->isTrue()->maybe()) {
                $innerKeyType = TypeCombinator::union($tKeyType, new IntegerType());
            }
        }


        $inner = new GenericObjectType($collectionName, [$innerKeyType, $valueType]);

        foreach (array_reverse($propertyTypes) as $propertyType) {
            if (count($propertyType->getConstantStrings()) > 0) {
                $types = [];
                foreach ($propertyType->getConstantStrings() as $constantString) {
                    if ($valueType->hasProperty($constantString->getValue())->yes()) {
                        $types[] = $valueType->getProperty($constantString->getValue(), new OutOfClassScope())->getReadableType();
                    } elseif ($valueType->hasOffsetValueType($constantString)->yes()) {
                        $types[] = $valueType->getOffsetValueType($constantString);
                    }
                }

                if (count($types) === 0) {
                    $keyType = new ConstantStringType('');
                } else {
                    $keyType = TypeCombinator::union(...$types);
                }
            } elseif ($propertyType->isCallable()->yes()) {
                $keyType = $propertyType->getCallableParametersAcceptors(new OutOfClassScope())[0]->getReturnType();
            } else {
                $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);
            }

            if ($keyType instanceof MixedType) {
                $keyType = new BenevolentUnionType([new IntegerType(), new StringType()]);
            }

            $inner = new GenericObjectType($collectionName, [$keyType, $inner]);
        }

        return $inner;
    }

    /**
     * @param class-string $name
     */
    private function unknownKeysType(string $name, Type $inner): Type
    {
        return new GenericObjectType($name, [
            new BenevolentUnionType([new IntegerType(), new StringType()]),
            new GenericObjectType($name, [
                new BenevolentUnionType([new IntegerType(), new StringType()]),
                $inner
            ])
        ]);
    }
}

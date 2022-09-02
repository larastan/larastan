<?php

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ModelCastHelper
{
    public function __construct(protected ReflectionProvider $reflectionProvider)
    {
    }

    public function getReadableType(string $cast, Type $originalType): Type
    {
        $cast = Str::before($cast, ':');

        $attributeType = match ($cast) {
            'int', 'integer' => new IntegerType(),
            'real', 'float', 'double' => new FloatType(),
            'decimal' => new AccessoryNumericStringType(),
            'string' => new StringType(),
            'bool', 'boolean' => new BooleanType(),
            'object' => new ObjectType('stdClass'),
            'array', 'json' => new ArrayType(new MixedType(), new MixedType()),
            'collection' => new ObjectType('Illuminate\Support\Collection'),
            'date', 'datetime' => new ObjectType('Carbon\Carbon'),
            'immutable_date', 'immutable_datetime' => new ObjectType('Carbon\CarbonImmutable'),
            'timestamp' => new IntegerType(),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast)) {
            return new MixedType();
        }

        $classReflection = $this->reflectionProvider->getClass($cast);

        if ($classReflection->isSubclassOf(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($castUsingReturn instanceof ObjectType && $castReflection = $castUsingReturn->getClassReflection()) {
                $classReflection = $castReflection;
            }
        }

        if ($classReflection->isSubclassOf(CastsAttributes::class)) {
            $methodReflection = $classReflection->getNativeMethod('get');

            return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
        }

        if ($classReflection->isSubclassOf(CastsInboundAttributes::class)) {
            return $originalType;
        }

        return new MixedType();
    }

    public function getWriteableType(string $cast, Type $originalType): Type
    {
        $attributeType = match ($cast) {
            'int', 'integer' => new IntegerType(),
            'real', 'float', 'double' => new FloatType(),
            'decimal' => new AccessoryNumericStringType(),
            'string' => new StringType(),
            'bool', 'boolean' => TypeCombinator::union(new BooleanType(), new ConstantIntegerType(0), new ConstantIntegerType(1)),
            'object' => new ObjectType('stdClass'),
            'array', 'json' => new ArrayType(new MixedType(), new MixedType()),
            'collection' => new ObjectType('Illuminate\Support\Collection'),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType('Carbon\CarbonImmutable'),
            'timestamp' => new IntegerType(),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast)) {
            return new MixedType();
        }

        $classReflection = $this->reflectionProvider->getClass($cast);

        if ($classReflection->isSubclassOf(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($castUsingReturn instanceof ObjectType && $castReflection = $castUsingReturn->getClassReflection()) {
                $classReflection = $castReflection;
            }
        }

        if (
            $classReflection->isSubclassOf(CastsAttributes::class)
            || $classReflection->isSubclassOf(CastsInboundAttributes::class)
        ) {
            $methodReflection = $classReflection->getNativeMethod('set');
            $parameters = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters();

            $valueParameter = Arr::first($parameters, fn (ParameterReflection $parameterReflection) => $parameterReflection->getName() === 'value');

            return $valueParameter->getType();
        }

        return new MixedType();
    }

    public function getDateType(): Type
    {
        $dateClass = class_exists(\Illuminate\Support\Facades\Date::class)
            ? \Illuminate\Support\Facades\Date::now()::class
            : '\Illuminate\Support\Carbon';

        if ($dateClass === '\Illuminate\Support\Carbon') {
            return TypeCombinator::union(new ObjectType($dateClass), new ObjectType(\Carbon\Carbon::class));
        }

        return new ObjectType($dateClass);
    }
}

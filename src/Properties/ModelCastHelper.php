<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function class_exists;
use function explode;

class ModelCastHelper
{
    public function __construct(protected ReflectionProvider $reflectionProvider)
    {
    }

    public function getReadableType(string $cast, Type $originalType): Type
    {
        $cast = $this->parseCast($cast);

        $attributeType = match ($cast) {
            'int', 'integer', 'timestamp' => new IntegerType(),
            'real', 'float', 'double' => new FloatType(),
            'decimal' => TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType()),
            'string' => new StringType(),
            'bool', 'boolean' => new BooleanType(),
            'object' => new ObjectType('stdClass'),
            'array', 'json' => new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()),
            'collection' => new ObjectType('Illuminate\Support\Collection'),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType('Carbon\CarbonImmutable'),
            'Illuminate\Database\Eloquent\Casts\AsArrayObject', 'Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject' => new ObjectType('Illuminate\Database\Eloquent\Casts\ArrayObject'),
            'Illuminate\Database\Eloquent\Casts\AsCollection', 'Illuminate\Database\Eloquent\Casts\AsEncryptedCollection' => new GenericObjectType('Illuminate\Support\Collection', [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]),
            'Illuminate\Database\Eloquent\Casts\AsStringable' => new ObjectType('Illuminate\Support\Stringable'),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast)) {
            return $originalType;
        }

        $classReflection = $this->reflectionProvider->getClass($cast);

        if ($classReflection->isEnum()) {
            return new ObjectType($cast);
        }

        if ($classReflection->isSubclassOf(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn  = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
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
        $cast = $this->parseCast($cast);

        $attributeType = match ($cast) {
            'int', 'integer', 'timestamp' => new IntegerType(),
            'real', 'float', 'double' => new FloatType(),
            'decimal' => TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType()),
            'string' => new StringType(),
            'bool', 'boolean' => TypeCombinator::union(new BooleanType(), new ConstantIntegerType(0), new ConstantIntegerType(1)),
            'object' => new ObjectType('stdClass'),
            'array', 'json' => new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()),
            'collection' => new ObjectType('Illuminate\Support\Collection'),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType('Carbon\CarbonImmutable'),
            'Illuminate\Database\Eloquent\Casts\AsArrayObject', 'Illuminate\Database\Eloquent\Casts\AsCollection',
            'Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject', 'Illuminate\Database\Eloquent\Casts\AsEncryptedCollection' => new MixedType(),
            'Illuminate\Database\Eloquent\Casts\AsStringable' => TypeCombinator::union(new StringType(), new ObjectType('Stringable')),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast)) {
            return $originalType;
        }

        $classReflection = $this->reflectionProvider->getClass($cast);

        if ($classReflection->isEnum()) {
            return new ObjectType($cast);
        }

        if ($classReflection->isSubclassOf(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn  = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if (
            $classReflection->isSubclassOf(CastsAttributes::class)
            || $classReflection->isSubclassOf(CastsInboundAttributes::class)
        ) {
            $methodReflection = $classReflection->getNativeMethod('set');
            $parameters       = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters();

            $valueParameter = Arr::first($parameters, static fn (ParameterReflection $parameterReflection) => $parameterReflection->getName() === 'value');

            return $valueParameter->getType();
        }

        return new MixedType();
    }

    public function getDateType(): Type
    {
        $dateClass = class_exists(Date::class)
            ? Date::now()::class
            : Carbon::class;

        if ($dateClass === Carbon::class) {
            return TypeCombinator::union(new ObjectType($dateClass), new ObjectType(\Carbon\Carbon::class));
        }

        return new ObjectType($dateClass);
    }

    private function parseCast(string $cast): string
    {
        foreach (explode(':', $cast) as $part) {
            // If the cast is prefixed with `encrypted:` we need to skip to the next
            if ($part === 'encrypted') {
                continue;
            }

            return $part;
        }

        return $cast;
    }
}

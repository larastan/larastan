<?php

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Support\Arr;
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
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

class ModelCastHelper
{
    public function __construct(protected ReflectionProvider $reflectionProvider)
    {
    }

    public function getReadableType(string $cast, Type $originalType): Type
    {
        $cast = EloquentCast::fromString($cast);

        $attributeType = match ($cast->type) {
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
            'Illuminate\Database\Eloquent\Casts\AsArrayObject', 'Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject' => new GenericObjectType('Illuminate\Database\Eloquent\Casts\ArrayObject', [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]),
            'Illuminate\Database\Eloquent\Casts\AsCollection', 'Illuminate\Database\Eloquent\Casts\AsEncryptedCollection' => new GenericObjectType('Illuminate\Support\Collection', [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]),
            'Illuminate\Database\Eloquent\Casts\AsStringable' => new ObjectType('Illuminate\Support\Stringable'),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast->type)) {
            return $originalType;
        }

        $classReflection = $this->reflectionProvider->getClass($cast->type);

        if ($classReflection->isEnum()) {
            return new ObjectType($classReflection->getName());
        }

        if ($classReflection->isSubclassOf(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if ($classReflection->is(CastsAttributes::class) || $classReflection->isSubclassOf(CastsAttributes::class)) {
            $methodReflection = $classReflection->getNativeMethod('get');

            $methodReturnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            // If the caster is generic and the first supplied parameter is a class, we will bind that in the template type map.
            return $this->resolveTemplateTypes($methodReturnType, $cast);
        }

        if ($classReflection->isSubclassOf(CastsInboundAttributes::class)) {
            return $originalType;
        }

        return new MixedType();
    }

    public function getWriteableType(string $cast, Type $originalType): Type
    {
        $cast = EloquentCast::fromString($cast);

        $attributeType = match ($cast->type) {
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

        if (! $this->reflectionProvider->hasClass($cast->type)) {
            return $originalType;
        }

        $classReflection = $this->reflectionProvider->getClass($cast->type);

        if ($classReflection->isEnum()) {
            return new ObjectType($cast->type);
        }

        if ($classReflection->isSubclassOf(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if ($classReflection->is(CastsAttributes::class)
            || $classReflection->isSubclassOf(CastsAttributes::class)
            || $classReflection->is(CastsInboundAttributes::class)
            || $classReflection->isSubclassOf(CastsInboundAttributes::class)) {
            $methodReflection = $classReflection->getNativeMethod('set');
            $parameters = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters();

            $valueParameter = Arr::first($parameters, fn (ParameterReflection $parameterReflection) => $parameterReflection->getName() === 'value');
            $valueParameterType = $valueParameter->getType();

            // If the caster is generic and the first supplied parameter is a class, we will bind that in the template type map.
            return $this->resolveTemplateTypes($valueParameterType, $cast);
        }

        return new MixedType();
    }

    public function getDateType(): Type
    {
        $dateClass = class_exists(\Illuminate\Support\Facades\Date::class)
            ? \Illuminate\Support\Facades\Date::now()::class
            : \Illuminate\Support\Carbon::class;

        if ($dateClass === \Illuminate\Support\Carbon::class) {
            return TypeCombinator::union(new ObjectType($dateClass), new ObjectType(\Carbon\Carbon::class));
        }

        return new ObjectType($dateClass);
    }

    /**
     * @param  Type  $valueParameterType
     * @param  EloquentCast  $cast
     * @return Type
     */
    private function resolveTemplateTypes(Type $valueParameterType, EloquentCast $cast): Type
    {
        if (TypeUtils::containsTemplateType($valueParameterType) && $this->reflectionProvider->hasClass($cast->parameters[0])) {
            $parameterType = new ObjectType($cast->parameters[0]);

            [$templateTypeReference] = $valueParameterType->getReferencedTemplateTypes(TemplateTypeVariance::createStatic());

            $templateTypeMap = new TemplateTypeMap([
                /** @phpstan-ignore-next-line PHPStan warns for this API not being covered by BC promise. */
                $templateTypeReference->getType()->getName() => $parameterType,
            ]);

            /** @phpstan-ignore-next-line PHPStan warns for this API not being covered by BC promise. */
            $valueParameterType = TemplateTypeHelper::resolveTemplateTypes($valueParameterType, $templateTypeMap);
        }

        return $valueParameterType;
    }
}

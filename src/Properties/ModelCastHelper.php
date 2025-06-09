<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Stringable as IlluminateStringable;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionException;
use stdClass;
use Stringable;

use function array_combine;
use function array_key_exists;
use function array_map;
use function array_merge;
use function class_exists;
use function explode;
use function str_replace;
use function version_compare;

class ModelCastHelper
{
    /** @var array<string, array<string, string>> */
    private array $modelCasts = [];

    public function __construct(
        protected ReflectionProvider $reflectionProvider,
    ) {
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
            'object' => new ObjectType(stdClass::class),
            'array', 'json' => new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()),
            'collection' => new ObjectType(Collection::class),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType(CarbonImmutable::class),
            AsArrayObject::class, AsEncryptedArrayObject::class => new ObjectType(ArrayObject::class),
            AsCollection::class, AsEncryptedCollection::class => new BenevolentUnionType([
                new GenericObjectType(Collection::class, [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]),
                new NullType(),
            ]),
            AsStringable::class => new ObjectType(IlluminateStringable::class),
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
            $castUsingReturn  = $methodReflection->getVariants()[0]->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if ($classReflection->isSubclassOf(CastsAttributes::class)) {
            $methodReflection = $classReflection->getNativeMethod('get');

            return $methodReflection->getVariants()[0]->getReturnType();
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
            'decimal' => TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType(), new FloatType()),
            'string' => new StringType(),
            'bool', 'boolean' => TypeCombinator::union(new BooleanType(), new ConstantIntegerType(0), new ConstantIntegerType(1)),
            'object' => new ObjectType(stdClass::class),
            'array', 'json' => new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()),
            'collection' => new ObjectType(Collection::class),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType(CarbonImmutable::class),
            AsArrayObject::class, AsCollection::class,
            AsEncryptedArrayObject::class, AsEncryptedCollection::class => new MixedType(),
            AsStringable::class => TypeCombinator::union(new StringType(), new ObjectType(Stringable::class)),
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
            $castUsingReturn  = $methodReflection->getVariants()[0]->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if (
            $classReflection->isSubclassOf(CastsAttributes::class)
            || $classReflection->isSubclassOf(CastsInboundAttributes::class)
        ) {
            $methodReflection = $classReflection->getNativeMethod('set');
            $parameters       = $methodReflection->getVariants()[0]->getParameters();

            $valueParameter = Arr::first($parameters, static fn (ParameterReflection $parameterReflection) => $parameterReflection->getName() === 'value');

            if ($valueParameter) {
                return $valueParameter->getType();
            }
        }

        return new MixedType();
    }

    public function getDateType(): Type
    {
        $dateClass = class_exists(Date::class)
            ? Date::now()::class
            : IlluminateCarbon::class;

        if ($dateClass === IlluminateCarbon::class) {
            return new ObjectType(Carbon::class);
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

    public function hasCastForProperty(ClassReflection $modelClassReflection, string $propertyName): bool
    {
        if (! array_key_exists($modelClassReflection->getName(), $this->modelCasts)) {
            $modelCasts = $this->getModelCasts($modelClassReflection);
        } else {
            $modelCasts = $this->modelCasts[$modelClassReflection->getName()];
        }

        return array_key_exists($propertyName, $modelCasts);
    }

    public function getCastForProperty(ClassReflection $modelClassReflection, string $propertyName): string|null
    {
        if (! array_key_exists($modelClassReflection->getName(), $this->modelCasts)) {
            $modelCasts = $this->getModelCasts($modelClassReflection);
        } else {
            $modelCasts = $this->modelCasts[$modelClassReflection->getName()];
        }

        return $modelCasts[$propertyName] ?? null;
    }

    /**
     * @return array<string, string>
     *
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    private function getModelCasts(ClassReflection $modelClassReflection): array
    {
        try {
            /** @var Model $modelInstance */
            $modelInstance = $modelClassReflection->getNativeReflection()->newInstanceWithoutConstructor();
        } catch (ReflectionException) {
            throw new ShouldNotHappenException();
        }

        $modelCasts = $modelInstance->getCasts();

        if (version_compare(LARAVEL_VERSION, '11.0.0', '>=')) {
            $castsMethodReturnType = $modelClassReflection->getMethod(
                'casts',
                new OutOfClassScope(),
            )->getVariants()[0]->getReturnType();

            if ($castsMethodReturnType->isConstantArray()->yes()) {
                $modelCasts = array_merge(
                    $modelCasts,
                    array_combine(
                        array_map(static fn ($key) => $key->getValue(), $castsMethodReturnType->getKeyTypes()), // @phpstan-ignore-line
                        array_map(static fn ($value) => str_replace('\\\\', '\\', $value->getValue()), $castsMethodReturnType->getValueTypes()), // @phpstan-ignore-line
                    ),
                );
            }
        }

        return $modelCasts;
    }
}

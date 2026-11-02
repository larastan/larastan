<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Str;
use Larastan\Larastan\Properties\ModelCastHelper;
use Larastan\Larastan\Properties\ModelPropertyHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function array_filter;
use function array_merge;
use function array_unique;
use function array_values;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function lcfirst;
use function str_contains;

final class ModelSerializationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ModelPropertyHelper $properties, private ModelCastHelper $casts, private ReflectionProvider $reflectionProvider)
    {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), ['toArray', 'attributesToArray'], true);
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
    {
        $types = [];

        foreach ($scope->getType($methodCall->var)->getObjectClassReflections() as $class) {
            if (! $class->is(Model::class)) {
                continue;
            }

            if ($class->isAbstract() || $class->getName() === Model::class) {
                return null;
            }

            // Defer to serializer and configuration overrides without executing application methods.
            foreach (array_unique([$methodReflection->getName(), 'attributesToArray', 'getAppends', 'getHidden', 'getVisible']) as $method) {
                if ($class->getNativeMethod($method)->getDeclaringClass()->getName() !== Model::class) {
                    return null;
                }
            }

            $appends = $this->configuredNames($class, 'Appends');
            $hidden  = $this->configuredNames($class, 'Hidden');
            $visible = $this->configuredNames($class, 'Visible');
            $names   = array_unique(array_merge($this->properties->getDatabasePropertyNames($class), $appends));

            if ($names === []) {
                return null;
            }

            $array = ConstantArrayTypeBuilder::createEmpty();

            foreach ($names as $name) {
                if (in_array($name, $hidden, true) || ($visible !== [] && ! in_array($name, $visible, true))) {
                    continue;
                }

                $array->setOffsetValueType(new ConstantStringType($name), $this->attributeType($class, $name, $scope), true);
            }

            // Partial selects omit columns; aggregates, aliases and loaded relations add keys.
            $array->makeUnsealed(new StringType(), new MixedType());
            $types[] = $array->getArray();
        }

        return $types === [] ? null : TypeCombinator::union(...$types);
    }

    /** @return array<string> */
    private function configuredNames(ClassReflection $class, string $attribute): array
    {
        $defaults = $class->getNativeReflection()->getProperty(lcfirst($attribute))->getDefaultValue();
        $defaults = is_array($defaults) ? array_filter($defaults, is_string(...)) : [];

        do {
            foreach ([$class, ...$class->getTraits()] as $source) {
                foreach ($source->getNativeReflection()->getAttributes() as $config) {
                    if ($config->getName() !== 'Illuminate\\Database\\Eloquent\\Attributes\\' . $attribute) {
                        continue;
                    }

                    $arguments = array_values($config->getArguments());
                    $columns   = is_array($arguments[0] ?? null) ? $arguments[0] : $arguments;

                    return array_unique(array_merge($defaults, array_filter($columns, is_string(...))));
                }
            }

            $class = $class->getParentClass();
        } while ($class !== null);

        return $defaults;
    }

    private function attributeType(ClassReflection $class, string $name, Scope $scope): Type
    {
        $accessor = $this->properties->hasAccessor($class, $name, false);

        if ($accessor) {
            $type = $this->properties->getAccessor($class, $name)->getReadableType();
        } elseif ($this->properties->hasDatabaseProperty($class, $name)) {
            $type = $this->properties->getDatabaseProperty($class, $name)->getReadableType();
        } else {
            return new MixedType();
        }

        $cast           = $this->casts->getCastForProperty($class, $name);
        $castClass      = explode(':', $cast ?? '')[0];
        $castReflection = $this->reflectionProvider->hasClass($castClass) ? $this->reflectionProvider->getClass($castClass) : null;
        $classCast      = $castReflection !== null && ! $castReflection->isEnum() && ! $castReflection->is(CastsInboundAttributes::class);
        $enumCast       = ! $accessor && $castReflection !== null && $castReflection->isEnum();
        $date           = ! $accessor || $class->hasNativeMethod(Str::camel($name));

        if ($accessor) {
            if ($classCast && $cast !== null) {
                // mutateAttributeForArray reads class casts before accessors, without their serialize hook.
                $type = $this->casts->getReadableType($cast, new MixedType());
                $date = false;
            }

            $cast = null;
        } elseif ($classCast) {
            if (in_array($castClass, [AsArrayObject::class, AsEncryptedArrayObject::class], true)) {
                return TypeCombinator::addNull(new ArrayType(TypeCombinator::union(new IntegerType(), new StringType()), new MixedType()));
            }

            if ($castReflection->is(Castable::class) && ! in_array($castClass, [AsCollection::class, AsEncryptedCollection::class, AsStringable::class], true)) {
                // A castUsing factory can choose a serializer dynamically.
                return new MixedType();
            }

            if ($castReflection->hasNativeMethod('serialize')) {
                $type = $castReflection->getNativeMethod('serialize')->getVariants()[0]->getReturnType();
                $date = false;
            }
        }

        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($class, $scope, $cast, $date, $enumCast): Type {
            if ($type instanceof UnionType) {
                return $traverse($type);
            }

            if ($date && (new ObjectType(DateTimeInterface::class))->isSuperTypeOf($type)->yes()) {
                if ($cast !== null && str_contains($cast, ':') && in_array(explode(':', $cast)[0], ['date', 'datetime', 'immutable_date', 'immutable_datetime'], true)) {
                    return new StringType();
                }

                return $class->getNativeMethod('serializeDate')->getVariants()[0]->getReturnType();
            }

            if ((new ObjectType(Enumerable::class))->isSuperTypeOf($type)->yes()) {
                $value = TypeTraverser::map($type->getIterableValueType(), static function (Type $item, callable $traverse) use ($scope): Type {
                    if ($item instanceof UnionType) {
                        return $traverse($item);
                    }

                    return (new ObjectType(Arrayable::class))->isSuperTypeOf($item)->yes()
                        ? $item->getMethod('toArray', $scope)->getVariants()[0]->getReturnType()
                        : $item;
                });

                return new ArrayType($type->getIterableKeyType(), $value);
            }

            if ((new ObjectType(Arrayable::class))->isSuperTypeOf($type)->yes()) {
                return $type->getMethod('toArray', $scope)->getVariants()[0]->getReturnType();
            }

            if ($enumCast && $type->isEnum()->yes()) {
                $values = [];

                foreach ($type->getEnumCases() as $case) {
                    $values[] = $case->getBackingValueType() ?? new ConstantStringType($case->getEnumCaseName());
                }

                return $values === [] ? new MixedType() : TypeCombinator::union(...$values);
            }

            return $type;
        });
    }
}

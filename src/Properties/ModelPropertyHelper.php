<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Larastan\Larastan\Reflection\ReflectionHelper;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function count;
use function explode;
use function in_array;
use function is_string;
use function method_exists;

class ModelPropertyHelper
{
    public function __construct(
        private TypeStringResolver $stringResolver,
        private ModelDatabaseHelper $modelDatabaseHelper,
        private ModelCastHelper $modelCastHelper,
    ) {
    }

    /**
     * Determine if the model has a database property.
     */
    public function hasDatabaseProperty(ClassReflection|string $classReflectionOrTable, string $propertyName): bool
    {
        if (is_string($classReflectionOrTable)) {
            if (Str::contains($classReflectionOrTable, '.')) {
                [$connection, $table] = explode('.', $classReflectionOrTable, limit: 2);
            } else {
                $table      = $classReflectionOrTable;
                $connection = $this->modelDatabaseHelper->getDefaultConnection();
            }

            return isset($this->modelDatabaseHelper->connections[$connection]->tables[$table]);
        }

        $classReflection = $classReflectionOrTable;

        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if ($classReflection->isAbstract()) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
        }

        $modelInstance = $this->modelDatabaseHelper->getModelInstance($classReflection);

        if ($modelInstance === null) {
            return false;
        }

        if ($propertyName === $modelInstance->getKeyName()) {
            return true;
        }

        return $this->modelDatabaseHelper->hasModelColumn($modelInstance, $propertyName);
    }

    public function getDatabaseProperty(ClassReflection $classReflection, string $propertyName): ModelProperty
    {
        $modelInstance = $this->modelDatabaseHelper->getModelInstance($classReflection);

        if ($modelInstance === null) {
            throw new ShouldNotHappenException();
        }

        if (
            $propertyName === $modelInstance->getKeyName()
            && ! $this->modelDatabaseHelper->hasModelColumn($modelInstance, $propertyName)
        ) {
            return new ModelProperty(
                $classReflection,
                $this->stringResolver->resolve($modelInstance->getKeyType()),
                $this->stringResolver->resolve($modelInstance->getKeyType()),
            );
        }

        $column = $this->modelDatabaseHelper->getModelColumn($modelInstance, $propertyName);

        if ($this->hasDate($modelInstance, $propertyName)) {
            $readableType = $this->modelCastHelper->getDateType();
            $writableType = TypeCombinator::union($this->modelCastHelper->getDateType(), new StringType());
        } else {
            $cast = $this->modelCastHelper->getCastForProperty($classReflection, $propertyName);

            if ($cast !== null) {
                $readableType = $this->modelCastHelper->getReadableType(
                    $cast,
                    $this->stringResolver->resolve($column->readableType),
                );
                $writableType = $this->modelCastHelper->getWriteableType(
                    $cast,
                    $this->stringResolver->resolve($column->writeableType),
                );
            } elseif (in_array($column->readableType, ['enum', 'set'], true)) {
                if ($column->options === null || count($column->options) < 1) {
                    $readableType = $writableType = new StringType();
                } else {
                    $readableType = $writableType = TypeCombinator::union(...array_map(
                        static fn ($option) => new ConstantStringType($option),
                        $column->options,
                    ));
                }
            } else {
                $readableType = $this->stringResolver->resolve($column->readableType);
                $writableType = $this->stringResolver->resolve($column->writeableType);
            }
        }

        if ($column->nullable) {
            $readableType = TypeCombinator::addNull($readableType);
            $writableType = TypeCombinator::addNull($writableType);
        }

        return new ModelProperty(
            $classReflection,
            $readableType,
            $writableType,
        );
    }

    /**
     * Determine if the model has a property accessor.
     *
     * @param bool $strictGenerics Requires the Attribute generics to be defined.
     */
    public function hasAccessor(ClassReflection $classReflection, string $propertyName, bool $strictGenerics = true): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        $camelCase = Str::camel($propertyName);

        if (! $classReflection->hasNativeMethod($camelCase)) {
            return $classReflection->hasNativeMethod('get' . Str::studly($propertyName) . 'Attribute');
        }

        $methodReflection = $classReflection->getNativeMethod($camelCase);

        if ($methodReflection->isPublic() || $methodReflection->isPrivate()) {
            return false;
        }

        $returnType = $methodReflection->getVariants()[0]->getReturnType();

        if (! $strictGenerics) {
            return (new ObjectType(Attribute::class))->isSuperTypeOf($returnType)->yes();
        }

        if ($returnType->getObjectClassReflections() === [] || ! $returnType->getObjectClassReflections()[0]->isGeneric()) {
            return false;
        }

        return (new GenericObjectType(Attribute::class, [new MixedType(), new MixedType()]))->isSuperTypeOf($returnType)->yes();
    }

    public function getAccessor(ClassReflection $classReflection, string $propertyName): ModelProperty
    {
        $studlyName = Str::studly($propertyName);

        if ($classReflection->hasNativeMethod($studlyName)) {
            $methodReflection = $classReflection->getNativeMethod($studlyName);

            $returnType = $methodReflection->getVariants()[0]->getReturnType();

            return new ModelProperty(
                $classReflection,
                $returnType->getTemplateType(Attribute::class, 'TGet'),
                $returnType->getTemplateType(Attribute::class, 'TSet'),
            );
        }

        $method = $classReflection->getNativeMethod('get' . Str::studly($propertyName) . 'Attribute');

        return new ModelProperty(
            $classReflection,
            $method->getVariants()[0]->getReturnType(),
            $method->getVariants()[0]->getReturnType(),
        );
    }

    private function hasDate(Model $modelInstance, string $propertyName): bool
    {
        $dates = $modelInstance->getDates();

        // In order to support SoftDeletes
        if (method_exists($modelInstance, 'getDeletedAtColumn')) {
            $dates[] = $modelInstance->getDeletedAtColumn();
        }

        return in_array($propertyName, $dates, true);
    }
}

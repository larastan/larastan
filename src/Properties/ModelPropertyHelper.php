<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Larastan\Larastan\Reflection\ReflectionHelper;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use ReflectionException;

use function array_key_exists;
use function count;
use function implode;
use function in_array;
use function method_exists;

class ModelPropertyHelper
{
    /** @var array<string, SchemaTable> */
    private array $tables = [];

    public function __construct(
        private TypeStringResolver $stringResolver,
        private MigrationHelper $migrationHelper,
        private SquashedMigrationHelper $squashedMigrationHelper,
        private ModelCastHelper $modelCastHelper,
    ) {
    }

    /**
     * Determine if the model has a database property.
     */
    public function hasDatebaseProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if ($classReflection->isAbstract()) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
        }

        if (! $this->migrationsLoaded()) {
            $this->loadMigrations();
        }

        try {
            /** @var Model $modelInstance */
            $modelInstance = $classReflection->getNativeReflection()->newInstanceWithoutConstructor();
        } catch (ReflectionException) {
            return false;
        }

        if ($propertyName === $modelInstance->getKeyName()) {
            return true;
        }

        $tableName = $modelInstance->getTable();

        if (! array_key_exists($tableName, $this->tables)) {
            return false;
        }

        return array_key_exists($propertyName, $this->tables[$tableName]->columns);
    }

    public function getDatabaseProperty(ClassReflection $classReflection, string $propertyName): ModelProperty
    {
        try {
            /** @var Model $modelInstance */
            $modelInstance = $classReflection->getNativeReflection()->newInstanceWithoutConstructor();
        } catch (ReflectionException) {
            throw new ShouldNotHappenException();
        }

        $tableName = $modelInstance->getTable();

        if (
            $propertyName === $modelInstance->getKeyName()
            && (! array_key_exists($tableName, $this->tables) || ! array_key_exists($propertyName, $this->tables[$tableName]->columns))
        ) {
            return new ModelProperty(
                $classReflection,
                $this->stringResolver->resolve($modelInstance->getKeyType()),
                $this->stringResolver->resolve($modelInstance->getKeyType()),
            );
        }

        $column = $this->tables[$tableName]->columns[$propertyName];

        if ($this->hasDate($modelInstance, $propertyName)) {
            $readableType = $this->modelCastHelper->getDateType();
            $writableType = TypeCombinator::union($this->modelCastHelper->getDateType(), new StringType());
        } elseif ($modelInstance->hasCast($propertyName)) {
            $cast = $modelInstance->getCasts()[$propertyName];

            $readableType = $this->modelCastHelper->getReadableType(
                $cast,
                $this->stringResolver->resolve($column->readableType),
            );
            $writableType = $this->modelCastHelper->getWriteableType(
                $cast,
                $this->stringResolver->resolve($column->writeableType),
            );
        } else {
            if (in_array($column->readableType, ['enum', 'set'], true)) {
                if ($column->options === null || count($column->options) < 1) {
                    $readableType = $writableType = new StringType();
                } else {
                    $readableType = $writableType = $this->stringResolver->resolve('\'' . implode('\'|\'', $column->options) . '\'');
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

        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

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

            /** @var GenericObjectType $returnType */
            $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            return new ModelProperty(
                $classReflection,
                $returnType->getTypes()[0],
                $returnType->getTypes()[1],
            );
        }

        $method = $classReflection->getNativeMethod('get' . Str::studly($propertyName) . 'Attribute');

        return new ModelProperty(
            $classReflection,
            $method->getVariants()[0]->getReturnType(),
            $method->getVariants()[0]->getReturnType(),
        );
    }

    private function migrationsLoaded(): bool
    {
        return count($this->tables) > 0;
    }

    private function loadMigrations(): void
    {
        // First try to create tables from squashed migrations, if there are any
        // Then scan the normal migration files for further changes to tables.
        $tables = $this->squashedMigrationHelper->initializeTables();

        $this->tables = $this->migrationHelper->initializeTables($tables);
    }

    private function hasDate(Model $modelInstance, string $propertyName): bool
    {
        $dates = $modelInstance->getDates();

        // In order to support SoftDeletes
        if (method_exists($modelInstance, 'getDeletedAtColumn')) {
            $dates[] = $modelInstance->getDeletedAtColumn();
        }

        return in_array($propertyName, $dates);
    }
}

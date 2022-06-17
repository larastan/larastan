<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use ArrayObject;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Reflection\ReflectionHelper;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;

/**
 * @internal
 */
final class ModelPropertyExtension implements PropertiesClassReflectionExtension
{
    /** @var array<string, SchemaTable> */
    private $tables = [];

    /** @var string */
    private $dateClass;

    public function __construct(private TypeStringResolver $stringResolver, private MigrationHelper $migrationHelper, private SquashedMigrationHelper $squashedMigrationHelper, private ReflectionProvider $reflectionProvider)
    {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if ($classReflection->isAbstract()) {
            return false;
        }

        if ($this->hasAttribute($classReflection, $propertyName)) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
        }

        if (count($this->tables) === 0) {
            // First try to create tables from squashed migrations, if there are any
            // Then scan the normal migration files for further changes to tables.
            $tables = $this->squashedMigrationHelper->initializeTables();

            $this->tables = $this->migrationHelper->initializeTables($tables);
        }

        if ($propertyName === 'id') {
            return true;
        }

        $modelName = $classReflection->getNativeReflection()->getName();

        try {
            $reflect = $this->reflectionProvider->getClass($modelName);

            /** @var Model $modelInstance */
            $modelInstance = $reflect->getNativeReflection()->newInstanceWithoutConstructor();

            $tableName = $modelInstance->getTable();
        } catch (ClassNotFoundException|\ReflectionException $e) {
            return false;
        }

        if (! array_key_exists($tableName, $this->tables)) {
            return false;
        }

        if (! array_key_exists($propertyName, $this->tables[$tableName]->columns)) {
            return false;
        }

        $this->castPropertiesType($modelInstance);

        $column = $this->tables[$tableName]->columns[$propertyName];

        [$readableType, $writableType] = $this->getReadableAndWritableTypes($column, $modelInstance);

        $column->readableType = $readableType;
        $column->writeableType = $writableType;

        $this->tables[$tableName]->columns[$propertyName] = $column;

        return true;
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        $modelName = $classReflection->getNativeReflection()->getName();

        try {
            $reflect = $this->reflectionProvider->getClass($modelName);

            /** @var Model $modelInstance */
            $modelInstance = $reflect->getNativeReflection()->newInstanceWithoutConstructor();

            $tableName = $modelInstance->getTable();
        } catch (ClassNotFoundException|\ReflectionException $e) {
            // `hasProperty` should return false if there was a reflection exception.
            // so this should never happen
            throw new ShouldNotHappenException();
        }

        if (
            (
                ! array_key_exists($tableName, $this->tables)
                || ! array_key_exists($propertyName, $this->tables[$tableName]->columns)
            )
            && $propertyName === 'id'
        ) {
            return new ModelProperty(
                $classReflection,
                $this->stringResolver->resolve($modelInstance->getKeyType()),
                $this->stringResolver->resolve($modelInstance->getKeyType())
            );
        }

        $column = $this->tables[$tableName]->columns[$propertyName];

        return new ModelProperty(
            $classReflection,
            $this->stringResolver->resolve($column->readableType),
            $this->stringResolver->resolve($column->writeableType)
        );
    }

    private function getDateClass(): string
    {
        if (! $this->dateClass) {
            $this->dateClass = class_exists(\Illuminate\Support\Facades\Date::class)
                ? '\\'.get_class(\Illuminate\Support\Facades\Date::now())
                : '\Illuminate\Support\Carbon';

            if ($this->dateClass === '\Illuminate\Support\Carbon') {
                $this->dateClass .= '|\Carbon\Carbon';
            }
        }

        return $this->dateClass;
    }

    /**
     * @param  Model  $modelInstance
     * @return string[]
     * @phpstan-return array<int, string>
     */
    private function getModelDateColumns(Model $modelInstance): array
    {
        $dateColumns = $modelInstance->getDates();

        if (method_exists($modelInstance, 'getDeletedAtColumn')) {
            $dateColumns[] = $modelInstance->getDeletedAtColumn();
        }

        return $dateColumns;
    }

    /**
     * @param  SchemaColumn  $column
     * @param  Model  $modelInstance
     * @return string[]
     * @phpstan-return array<int, string>
     */
    private function getReadableAndWritableTypes(SchemaColumn $column, Model $modelInstance): array
    {
        $readableType = $column->readableType;
        $writableType = $column->writeableType;

        if (in_array($column->name, $this->getModelDateColumns($modelInstance), true)) {
            return [$this->getDateClass().($column->nullable ? '|null' : ''), $this->getDateClass().'|string'.($column->nullable ? '|null' : '')];
        }

        switch ($column->readableType) {
            case 'string':
            case 'int':
            case 'float':
                $readableType = $writableType = $column->readableType.($column->nullable ? '|null' : '');
                break;

            case 'boolean':
            case 'bool':
                switch ((string) config('database.default')) {
                    case 'sqlite':
                    case 'mysql':
                        $writableType = '0|1|bool';
                        $readableType = 'bool';
                        break;
                    default:
                        $readableType = $writableType = 'bool';
                        break;
                }
                break;
            case 'enum':
            case 'set':
                if (! $column->options) {
                    $readableType = $writableType = 'string';
                } else {
                    $readableType = $writableType = '\''.implode('\'|\'', $column->options).'\'';
                }

                break;

            default:
                break;
        }

        return [$readableType, $writableType];
    }

    private function castPropertiesType(Model $modelInstance): void
    {
        $casts = $modelInstance->getCasts();
        foreach ($casts as $name => $type) {
            if (! array_key_exists($name, $this->tables[$modelInstance->getTable()]->columns)) {
                continue;
            }

            switch ($type) {
                case 'boolean':
                case 'bool':
                    $realType = 'boolean';
                    break;
                case 'string':
                    $realType = 'string';
                    break;
                case 'array':
                case 'json':
                    $realType = 'array';
                    break;
                case 'object':
                    $realType = 'object';
                    break;
                case 'int':
                case 'integer':
                case 'timestamp':
                    $realType = 'integer';
                    break;
                case 'real':
                case 'double':
                case 'float':
                    $realType = 'float';
                    break;
                case 'date':
                case 'datetime':
                    $realType = $this->getDateClass();
                    break;
                case 'collection':
                    $realType = '\Illuminate\Support\Collection';
                    break;
                case 'Illuminate\Database\Eloquent\Casts\AsArrayObject':
                    $realType = ArrayObject::class;
                    break;
                case 'Illuminate\Database\Eloquent\Casts\AsCollection':
                    $realType = '\Illuminate\Support\Collection<array-key, mixed>';
                    break;
                default:
                    $realType = class_exists($type) ? ('\\'.$type) : 'mixed';
                    break;
            }

            if ($this->tables[$modelInstance->getTable()]->columns[$name]->nullable) {
                $realType .= '|null';
            }

            $this->tables[$modelInstance->getTable()]->columns[$name]->readableType = $realType;
            $this->tables[$modelInstance->getTable()]->columns[$name]->writeableType = $realType;
        }
    }

    private function hasAttribute(ClassReflection $classReflection, string $propertyName): bool
    {
        if ($classReflection->hasNativeMethod('get'.Str::studly($propertyName).'Attribute')) {
            return true;
        }

        $camelCase = Str::camel($propertyName);

        if ($classReflection->hasNativeMethod($camelCase)) {
            $methodReflection = $classReflection->getNativeMethod($camelCase);

            if ($methodReflection->isPublic() || $methodReflection->isPrivate()) {
                return false;
            }

            $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

            if (! (new ObjectType(Attribute::class))->isSuperTypeOf($returnType)->yes()) {
                return false;
            }

            return true;
        }

        return false;
    }
}

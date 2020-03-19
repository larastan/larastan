<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Iterator;
use PHPStan\Parser\CachedParser;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

/**
 * @internal
 */
final class ModelPropertyExtension implements PropertiesClassReflectionExtension
{
    /** @var CachedParser */
    private $parser;

    /** @var SchemaTable[] */
    private $tables = [];

    /** @var TypeStringResolver */
    private $stringResolver;

    /** @var string */
    private $dateClass;

    public function __construct(CachedParser $parser, TypeStringResolver $stringResolver)
    {
        $this->parser = $parser;
        $this->stringResolver = $stringResolver;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if ($classReflection->isAbstract()) {
            return false;
        }

        if ($classReflection->hasNativeMethod('get'.Str::studly($propertyName).'Attribute')) {
            return false;
        }

        if (count($this->tables) === 0) {
            $this->initializeTables();
        }

        /** @var Model $modelInstance */
        $modelInstance = $classReflection->getNativeReflection()->newInstance();
        $tableName = $modelInstance->getTable();

        if (! array_key_exists($tableName, $this->tables)) {
            return false;
        }

        if (! array_key_exists($propertyName, $this->tables[$tableName]->columns)) {
            return false;
        }

        $column = $this->tables[$tableName]->columns[$propertyName];

        [$readableType, $writableType] = $this->getReadableAndWritableTypes($column, $modelInstance);

        $this->castPropertiesType($modelInstance);

        $column->readableType = $readableType;
        $column->writeableType = $writableType;

        return true;
    }

    public function getProperty(
        ClassReflection $classReflection,
        string $propertyName
    ): PropertyReflection {
        /** @var Model $modelInstance */
        $modelInstance = $classReflection->getNativeReflection()->newInstance();
        $tableName = $modelInstance->getTable();
        $column = $this->tables[$tableName]->columns[$propertyName];

        return new ModelProperty(
            $classReflection,
            $this->stringResolver->resolve($column->readableType),
            $this->stringResolver->resolve($column->writeableType)
        );
    }

    private function initializeTables(): void
    {
        if (! is_dir(database_path().'/migrations')) {
            return;
        }

        $schemaAggregator = new SchemaAggregator();
        $files = $this->getMigrationFiles(database_path().'/migrations');
        $filesArray = iterator_to_array($files);
        ksort($filesArray);

        $this->requireFiles($filesArray);

        foreach ($filesArray as $file) {
            $schemaAggregator->addStatements($this->parser->parseFile($file->getPathname()));
        }

        $this->tables = $schemaAggregator->tables;
    }

    /**
     * @param string $path
     *
     * @return Iterator<SplFileInfo>
     */
    private function getMigrationFiles(string $path): Iterator
    {
        return new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)), '/\.php$/i');
    }

    /**
     * @param SplFileInfo[] $files
     */
    private function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            require_once $file;
        }
    }

    private function getDateClass(): string
    {
        return $this->dateClass ?? class_exists(\Illuminate\Support\Facades\Date::class)
            ? '\\'.get_class(\Illuminate\Support\Facades\Date::now())
            : '\Illuminate\Support\Carbon';
    }

    /**
     * @param SchemaColumn $column
     * @param Model $modelInstance
     *
     * @return string[]
     * @phpstan-return array<int, string>
     */
    private function getReadableAndWritableTypes(SchemaColumn $column, Model $modelInstance): array
    {
        $readableType = 'mixed';
        $writableType = 'mixed';

        if (in_array($column->name, $modelInstance->getDates(), true)) {
            return [$this->getDateClass().($column->nullable ? '|null' : ''), $this->getDateClass().'|string'.($column->nullable ? '|null' : '')];
        }

        switch ($column->readableType) {
            case 'string':
            case 'int':
            case 'float':
                $readableType = $writableType = $column->readableType.($column->nullable ? '|null' : '');
                break;

            case 'bool':
                switch ((string) config('database.default')) {
                    case 'sqlite':
                    case 'mysql':
                        $writableType = '0|1|bool';
                        $readableType = '0|1';
                        break;
                    default:
                        $readableType = $writableType = 'bool';
                        break;
                }
                break;
            case 'enum':
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
                    $realType = $this->dateClass;
                    break;
                case 'collection':
                    $realType = '\Illuminate\Support\Collection';
                    break;
                default:
                    $realType = class_exists($type) ? ('\\'.$type) : 'mixed';
                    break;
            }

            if (! array_key_exists($name, $this->tables[$modelInstance->getTable()]->columns)) {
                continue;
            }

            $this->tables[$modelInstance->getTable()]->columns[$name]->readableType = $realType;
            $this->tables[$modelInstance->getTable()]->columns[$name]->writeableType = $realType;
        }
    }
}

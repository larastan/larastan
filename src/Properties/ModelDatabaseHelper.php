<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Concerns\HasContainer;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use ReflectionException;

use function count;
use function is_string;

final class ModelDatabaseHelper
{
    use HasContainer;

    /** @var array<string, SchemaConnection> */
    public array $connections = [];

    private string $defaultConnection;

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private SquashedMigrationHelper $squashedMigrationHelper,
        private MigrationHelper $migrationHelper,
    ) {
    }

    public function getModelConnectionName(Model $model): string
    {
        return $model->getConnectionName() ?? $this->getDefaultConnection();
    }

    public function getModelTable(Model $model): SchemaTable
    {
        return $this->connections[$this->getModelConnectionName($model)]
            ->tables[$model->getTable()];
    }

    public function hasConnection(string $connection): bool
    {
        $this->ensureInitialized();

        return isset($this->connections[$connection]);
    }

    public function hasModelTable(Model $model): bool
    {
        $connectionName = $this->getModelConnectionName($model);

        if (! $this->hasConnection($connectionName)) {
            return false;
        }

        return isset($this->connections[$connectionName]->tables[$model->getTable()]);
    }

    public function hasModelColumn(Model $model, string $column): bool
    {
        if (! $this->hasModelTable($model)) {
            return false;
        }

        return isset($this->getModelTable($model)->columns[$column]);
    }

    public function getOrCreateConnection(string|null $connectionName = null): SchemaConnection
    {
        $connectionName ??= $this->getDefaultConnection();

        if ($this->hasConnection($connectionName)) {
            return $this->connections[$connectionName];
        }

        $connection = new SchemaConnection($connectionName);

        $this->setConnection($connection);

        return $connection;
    }

    public function getModelColumn(Model $model, string $column): SchemaColumn
    {
        return $this->getModelTable($model)->columns[$column];
    }

    public function setConnection(SchemaConnection $connection): void
    {
        $this->connections[$connection->name] = $connection;
    }

    public function dropConnection(string $connectionName): void
    {
        unset($this->connections[$connectionName]);
    }

    public function getModelInstance(ClassReflection|string $class): Model|null
    {
        if (is_string($class)) {
            $class = $this->reflectionProvider->getClass($class);
        }

        try {
            /** @var Model $modelInstance */
            $modelInstance = $class->getNativeReflection()->newInstanceWithoutConstructor();
        } catch (ReflectionException) {
            return null;
        }

        return $modelInstance;
    }

    public function ensureInitialized(): void
    {
        if (count($this->connections) !== 0) {
            return;
        }

        // First try to create tables from any squashed migrations.
        // Then scan the normal migration files for further changes to tables.
        $this->squashedMigrationHelper->parseSchemaDumps($this);
        $this->migrationHelper->parseMigrations($this);
    }

    public function getDefaultConnection(): string
    {
        if (! isset($this->defaultConnection)) {
            $this->defaultConnection = $this->resolve('config')['database.default'] ?? 'default';
        }

        return $this->defaultConnection;
    }
}

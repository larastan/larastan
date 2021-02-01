# Custom config parameters

All custom config parameters that are defined by Larastan are listed here.

## `noUnnecessaryCollectionCall`, `noUnnecessaryCollectionCallOnly`, `noUnnecessaryCollectionCallExcept`

These parameters are related to the `NoUnnecessaryCollectionCall` rule. You can find the details about these parameters and the rule [here](rules.md#NoUnnecessaryCollectionCall).

## `databaseMigrationsPath`

By default, the default Laravel database migration path (`database/migrations`) is used to scan migration files to understand the table structure and model properties. If you have database migrations in other place than the default, you can use this config parameter to tell Larastan where the database migrations are stored.

You can give an absolute path, or a path relative to the PHPStan config file.

#### Example
```neon
parameters:
    databaseMigrationsPath: ../custom/migrations/path
```

## `additionalDatabaseMigrationPaths`

If your Laravel configuration uses database paths in multiple directories, you can extend `databaseMigrationsPath` by setting `additionalDatabaseMigrationPaths`.

You can give absolute paths, or paths relative to the PHPStan config file.

#### Example
```neon
parameters:
    additionalDatabaseMigrationPaths:
        - app/Domain/DomainA/migrations
        - app/Domain/DomainB/migrations
```

## `checkModelProperties`
**default**: `false`

This config parameter enables the checks for model properties that are passed to methods. You can read the details [here](rules.md#modelpropertyrule).

To enable you can set it to `true`:

```neon
parameters:
    checkModelProperties: true
```

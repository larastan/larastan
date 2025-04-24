# Custom config parameters

All custom config parameters that are defined by Larastan are listed here.

## `noUnnecessaryCollectionCall`, `noUnnecessaryCollectionCallOnly`, `noUnnecessaryCollectionCallExcept`

These parameters are related to the `NoUnnecessaryCollectionCall` rule. You can find the details about these parameters and the rule [here](rules.md#NoUnnecessaryCollectionCall).

## `databaseMigrationsPath`

By default, the default Laravel database migration path (`database/migrations`) is used to scan migration files to understand the table structure and model properties. If you have database migrations in other place than the default, you can use this config parameter to tell Larastan where the database migrations are stored.

You can give absolute paths, or paths relative to the PHPStan config file.

### Example
```neon
parameters:
    databaseMigrationsPath:
        - app/Domain/DomainA/migrations
        - app/Domain/DomainB/migrations
        - modules/*/migrations
```

**Note:** If your migrations are using `if` statements to conditionally alter database structure (ex: create table only if it's not there, add column only if table exists and column does not etc...) Larastan will assume those if statements evaluate to true and will consider everything from the `if` body.

## `disableMigrationScan`
**default**: `false`

You can disable use this config to disable migration scanning.

### Example
```neon
parameters:
    disableMigrationScan: true
```

## `squashedMigrationsPath`

By default, Larastan will check `database/schema` directory to find schema dumps. If you have them in other locations or if you have multiple folders, you can use this config option to add them.

### Example
```neon
parameters:
    squashedMigrationsPath:
        - app/Domain/DomainA/schema
        - app/Domain/DomainB/schema
        - modules/*/schema
```

### PostgreSQL

The package used to parse the schema dumps, [iamcal/sql-parser](https://github.com/iamcal/sql-parser), is primarily focused on the MySQL dialect.
It can read (or rather, try to read) PostgreSQL dumps provided they are in the *plain text (and not the 'custom') format*, but the mileage may vary as problems have been noted with timestamp columns and lengthy parse time on more complicated dumps.

The viable options for PostgreSQL at the moment are:
1. Use the [laravel-ide-helper](https://github.com/barryvdh/laravel-ide-helper) package to write PHPDocs directly to the Models. 
2. Use the [laravel-migrations-generator](https://github.com/kitloong/laravel-migrations-generator) to generate migration files (or a singular squashed migration file) for Larastan to scan with the `databaseMigrationsPath` setting.

## `disableSchemaScan`
**default**: `false`

You can disable use this config to disable schema scanning.

### Example
```neon
parameters:
    disableSchemaScan: true
```

## `checkModelProperties`
**default**: `false`

This config parameter enables the checks for model properties that are passed to methods. You can read the details [here](rules.md#modelpropertyrule).

To enable you can set it to `true`:

```neon
parameters:
    checkModelProperties: true
```

## `checkModelAppends`
**default**: `true`

This config parameter enables the checks the model's $appends property for computed properties. You can read the details [here](rules.md#modelappendsrule).

To disable you can set it to `false`:

```neon
parameters:
    checkModelAppends: false
```

## `checkConfigTypes`
**default**: `false`

This config parameter enables the checks for `config` helper function return type and `\Illuminate\Config\Repository::get` method return type.

By default, Larastan assumes your config files are under `/config` directory. It uses `config_path` function from Laravel to determine this. But if you have unconventional config file structure, you can use `configDirectories` config parameter to tell Larastan where your config files are stored.

```neon
parameters:
    configDirectories:
        - src/config
        - modules/*/config
```

### Example
With this parameter enabled, the following code
```php
// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],
];

// test.php
echo config('auth.defaults');
```

would give an error:
```
Parameter #1 (array{guard: 'web', passwords: 'users'}) of echo cannot be converted to string.
```

### Performance
Larastan parses the config files to be able to understand their structure. But it does this lazily. Only the config files that were accessed during the analysis will be parsed. Also, it has internal caching mechanisms so that one file will not be parsed twice.

**Note**: Although everything is done lazily, parsing big number of config files might add a little bit of overhead. Use with caution.

## `generalizeEnvReturnType`
**default**: `false`

This config parameter, if enabled, will generalize the return type of `env` function if the default argument is passed. For example:
```php
$foo = env('FOO', 'bar');
\PHPStan\dumpType($foo);
```

will dump `string` when the parameter is enabled. Or
```php
$foo = env('FOO', fn() => 'bar');
\PHPStan\dumpType($foo);
```
will also dump `string` when the parameter is enabled.

The reasoning behind it is that the default value is always used when the environment variable is not set. And for almost all the cases the default value should be the same type as the actual env variable. So it makes sense to generalize the return type to the type of the default value.

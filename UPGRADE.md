# Upgrade Guide

## Upgrading to `4.0` from `3.x`

### Laravel and PHP requirements

Laravel 11, Laravel 12, Laravel 13 releases older than the minimum version, and PHP 8.2 are no longer supported. Upgrade your application to the Laravel and PHP versions required by [composer.json](composer.json) before upgrading Larastan. Applications that cannot upgrade can continue using Larastan 3.x.

Laravel's `dev-master` branch is also supported and tested with matching Testbench and Canvas development dependencies. See the [test workflow](.github/workflows/tests.yml) for the tested combinations.

### Internal cleanup

- Removed compatibility branches and obsolete tests for unsupported Laravel versions. The active version-specific stub declarations are now shared in `stubs/common/`.
- Consolidated the remaining version-specific type fixtures into the general and feature-specific fixtures, preserving their assertions.

### Arrayable templates

Both `Arrayable<TKey, TValue>` and `Arrayable<TReturn>` are supported on all supported Laravel versions. Legacy annotations are normalized to `Arrayable<array<TKey, TValue>>`, using Laravel 14's covariant array-return contract. Diagnostics therefore display the single-template form. Array shapes and lists retain their structure in `toArray()` results.

`Arrayable` is now covariant, so `Arrayable<string, int>` is accepted where `Arrayable<string, mixed>` is expected, and a bare `Arrayable` no longer requires generic types. Remove baseline entries for these errors. The `TKey` and `TValue` templates are replaced by `TReturn`, which affects code and extensions that read `Arrayable`'s templates by name.

### Translation helper

`trans()` and `trans(null)` now return the translator type declared by the installed Laravel version: the translation contract on Laravel 13 and the concrete translator on Laravel's development branch. Calls with a nullable key now include the translator in their return type; calls with a string key retain their existing `array|string` behavior.

### Job and event dispatch diagnostics

Dispatch argument checking now uses PHPStan's public `NodeCallbackInvoker` API instead of its internal `FunctionCallParametersCheck`. Error messages use PHPStan's constructor wording. For example, `Job class ExampleJob constructor invoked with 1 parameter in ExampleJob::dispatch(), 2 required.` becomes `Class ExampleJob constructor invoked with 1 parameter, 2 required.` Private and protected constructors remain supported; their argument errors use PHPStan's closure wording.

Regenerate affected baseline entries and update message-based ignores. Errors for dispatching arguments to a class without a constructor now use `new.noConstructor` instead of `larastan.jobs.noConstructor` or `larastan.events.noConstructor`. Argument-count and argument-type errors retain the `arguments.count` and `argument.type` identifiers.

## Upgrading to `3.0` from `2.x`

This is a new major release with lots of breaking changes. 

**Please first read PHPStan's 2.0 upgrade guide [here](https://github.com/phpstan/phpstan/blob/2.0.x/UPGRADING.md) carefully.**

### Correct return types for model relation methods
######  Likelihood Of Impact: High

Normally PHPStan warns the users when a return type of method does not provide its generic types. For example, the following code will produce a PHPStan error:

```php
class User extends Model
{
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}

// Method User::posts() return type with generic class HasMany does not specify its types: TRelatedModel, TDeclaringModel
```

In the previous versions of Larastan, in this case Larastan would parse the model file and read the method body to understand the generic types of the relation. But this approach is slow (because it requires parsing the file) and the maintenance of this feature is hard. So in this version, Larastan will not parse the method body to understand the generic types of the relation. Instead, you need to provide the correct generic types in the return type of the relation method. Here is how you can fix the above example:

```php
class User extends Model
{
    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
```

Manually adding these annotations can be tedious. To help with this, we've created a [Rector](https://github.com/rectorphp/rector) rule that can automatically add them for you! You can use [this rule](https://github.com/driftingly/rector-laravel/blob/main/docs/rector_rules_overview.md#addgenericreturntypetorelationsrector) to automatically add the correct generic annotations. It detects your Laravel version and adds the appropriate generic types accordingly.

If you're not currently using Rector, or can't use it due to dependency conflicts, don't worry! We've also prepared a simple script that can run Rector for you without requiring a full installation.

First install [`cpx`](https://cpx.dev/):
```bash
composer global require cpx/cpx
```

Then download [this script](https://gist.github.com/canvural/7385ec70d2719e9961886430fbb4798c) from the gist and run with:
```bash
cpx exec /path/to/script/cpx-rector-larastan-upgrade.php
```

### Template annotation renames
######  Likelihood Of Impact: Low

These changes were made in Laravel itself, so here we needed to follow the same changes. If you were using any of the template annotations in your code, you need to update them as follows:

- `TModelClass` annotation of the Eloquent Builder class is renamed to `TModel`.
- `TChildModel` annotation of the relation classes is renamed to `TDeclaringModel`.

### Code related to Carbon has been removed
######  Likelihood Of Impact: Low

Larastan 3.x removed some code that was handling some edge cases related to Carbon. This code is now removed because the official Carbon PHPStan extension can do the same things. If you are using `phpstan-extension-installer` in your project you are already using the Carbon PHPStan extensions and there is nothing else to do. If not, you can add `vendor/nesbot/carbon/extension.neon` to your `phpstan.neon` file to enable the Carbon PHPStan extension.

### `checkPhpDocMissingReturn: false` config option removed
######  Likelihood Of Impact: Low
For some historical reason Larastan was setting `checkPhpDocMissingReturn: false` config option. Now the option is removed from Larastan, it'll use the behaviour from PHPStan itself. If you want the old behaviour back you can add the option to your own config.

### `noEnvCallsOutsideOfConfig` and `checkModelAppends` options are enabled by default
Starting from Larastan 3.0 the `NoEnvCallsOutsideOfConfigRule` and `ModelAppendsRule` are enabled by default.

### Removed `*.blade.php` from `excludePaths` config option
######  Likelihood Of Impact: Low
We've removed `*.blade.php` from `excludePaths` config option. If you were analysing paths containing Blade files and now are getting errors for them you can add it back in your configuration via:
```neon
excludePaths:
    - *.blade.php
```

## Upgrading to `2.9.6` from `2.9.5`

This release adds support for Laravel 11 `casts` method. If you are using the `casts` method in your models, you will need to update the return type of the `casts` method to `array` in your model classes. Also, you'd need to provide the correct array shape for the return type. So that Larastan will recognize the model casts. Here is an example:

```php
/**
 * @return array{is_admin: 'boolean', meta: 'array'}
 */
public function casts(): array
{
    return [
        'is_admin' => 'boolean',
        'meta' => 'array',
    ];
}
```

## Upgrading to `2.9.2` from `2.9.0`

- The UnusedViewsRule has been changed to specify the absolute path of the unused view, rather than the view name. This may mean that baselines will need regenerating to account for this change.

## Upgrading to `2.7.0` from `2.6.5`

### Organization Change

Starting with Larastan 2.7.0, the Larastan repository will now be managed under the [Larastan](https://github.com/larastan) organization. To receive the latest updates, please modify your composer's Larastan entry as follows:

```diff
    "require-dev": {
-        "nunomaduro/larastan": "^2.6.0",
+        "larastan/larastan": "^2.7.0",
    },
```

If you are using the `includes` option in your `phpstan.neon` configuration file, please update it as well:

```diff
includes:
-    - ./vendor/nunomaduro/larastan/extension.neon
+    - ./vendor/larastan/larastan/extension.neon
```

## Upgrading to `2.0.0` from `1.x`

### Eloquent Collection now requires 2 generic types

In Larastan 1.x, Eloquent Collection was defined with only one generic type. Just the model type. But starting with Laravel 9, all collection stubs are now moved into the Laravel core. And as part of that migration process, the collection type is now defined with 2 generic types. First is collection item key, second is collection item value. So if you had a docblock like this `Collection<User>` now you should change it to `Collection<int, User>`.

### Removed configuration `checkGenericClassInNonGenericObjectType: false` from default config

In Larastan 1.x, we set the `checkGenericClassInNonGenericObjectType` to `false` by default. In 2.0.0, this is removed from the config. If you want to keep the same behavior, you can set it to `false` in your config.

## Upgrading to `0.7.11`

### Laravel 8 Model Factory support

`0.7.11` adds support for Laravel 8 model factory return types and methods. But there is one step you need to do before taking advantage of this.

Because `Factory` class is marked as generic now, you need to also specify this in your model factories.

So for example if you have `UserFactory` class, the following change needs to be made:
```php
<?php

/** @extends Factory<User> */
class UserFactory extends Factory
{
    // ...
}
```

So general rule is that `@extends Factory<MODELNAME>` PHPDoc needs to be added to factory class, where `MODELNAME` is the model class name which this factory is using.

## Upgrading to `0.7.0`

### `databaseMigrationsPath` parameter is now an array

`databaseMigrationsPath` parameter is changed to be an `array` from `string`. To allow multiple migration paths.

## Upgrading to 0.6

In previous versions of Larastan, `reportUnmatchedIgnoredErrors` config value was set to `false` by Larastan. Larastan no longer ignores errors on your behalf. Here is how you can fix them yourself:

### Result of function abort \(void\) is used

Stop `return`-ing abort.

```diff
-return abort(401);
+abort(401);
```

### Call to an undefined method Illuminate\\Support\\HigherOrder

Larastan still does not understand this particular magic, you can
[ignore it yourself](docs/errors-to-ignore.md#higher-order-messages) for now.

### Method App\\Exceptions\\Handler::render\(\) should return Illuminate\\Http\\Response but returns Symfony\\Component\\HttpFoundation\\Response

Fix the docblock.

```diff
-    * @return Illuminate\Http\Response|Symfony\Component\HttpFoundation\Response
+    * @return Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
```

### Property App\\Http\\Middleware\\TrustProxies::\$headers \(string\) does not accept default value of type int

Fix the docblock.

```diff
-    * @var string
+    * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
```

## Upgrading to 0.5.8

### Custom collections
If you are taking advantage of custom Eloquent Collections for your models, you have to mark your custom collection class as generic like so:
```php
/**
 * @template TModel
 * @extends Collection<TModel>
 */
class CustomCollection extends Collection
{
}
```
If your IDE complains about the `template` or `extends` annotation you may also use the PHPStan specific annotations `@phpstan-template` and `@phpstan-extends`

Also in your model file where you are overriding the `newCollection` method, you have to specify the return type like so:

```php
/**
 * @param array<int, YourModel> $models
 *
 * @return CustomCollection<YourModel>
 */
public function newCollection(array $models = []): CustomCollection
{
    return new CustomCollection($models);
}
```

If your IDE complains about the return type annotation you may also use the PHPStan specific return type `@phpstan-return`

## Upgrading to 0.5.6

### Generic Relations
Eloquent relations are now generic classes. Internally, this makes couple of things easier and more flexible. In general it shouldn't affect your code. The only caveat is if you define your custom relations. If you do that, you have to mark your custom relation class as generic like so:

```php
/**
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @extends Relation<TRelatedModel>
 */
class CustomRelation extends Relation
{
    //...
}
```

## Upgrading To 0.5.3 From 0.5.2

#### Eloquent Resources
In order to perform proper analysis on your Eloquent resources, you must typehint the underlying Eloquent model class.
This will inform PHPStan that this resource uses `User` model. So calls to `$this` with model property or methods will be inferred correctly.

```php
/**
 * @extends JsonResource<User>
 */
class UserResource extends JsonResource
{
...
}

```

## Upgrading To 0.5.1 From 0.5.0

#### Eloquent Model property types
0.5.1 introduces ability to infer Eloquent model property types. To take advantage of this you have to remove any model class from `universalObjectCratesClasses` PHPStan configuration parameter, if you added any earlier.

#### Custom Eloquent Builders
If you are taking advantage of custom Eloquent Builders for your models, you have to mark your custom builder class as generic like so:
```php
/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @extends Builder<TModel>
 */
class CustomBuilder extends Builder
{
}
```
If your IDE complains about the `template` or `extends` annotation you may also use the PHPStan specific annotations `@phpstan-template` and `@phpstan-extends`

Also in your model file where you are overriding the `newEloquentBuilder` method, you have to specify the return type like so:

```php
/**
 * @param \Illuminate\Database\Query\Builder $query
 *
 * @return CustomBuilder<YourModelWithCustomBuilder>
 */
public function newEloquentBuilder($query): CustomBuilder
{
    return new CustomBuilder($query);
}
```

If your IDE complains about the return type annotation you may also use the PHPStan specific return type `@phpstan-return`

#### Collection generics
Generic stubs added to Eloquent and Support collections. Larastan is able to take advantage of this and returns the correct collection with its items defined. For example `Collection<User>` represents collection of users. But in case Larastan fails to do so in any case, you can assist with adding a typehint with the appropriate annotation like `@var`, `@param` or `@return` using the syntax `Collection<Model>`

## Upgrading To 0.5 From 0.4

### Updating Dependencies

Update your `nunomaduro/larastan` dependency to `^0.5` in your `composer.json` file.

### `artisan code:analyse`

The artisan `code:analyse` command is no longer available. Therefore, you need to:

1. Start using the phpstan command to launch Larastan.

```bash
./vendor/bin/phpstan analyse
```

If you are getting the error `Allowed memory size exhausted`, then you can use the `--memory-limit` option fix the problem:

```bash
./vendor/bin/phpstan analyse --memory-limit=2G
```

2. Create a `phpstan.neon` or `phpstan.neon.dist` file in the root of your application that might look like this:

```
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:

    paths:
        - app

    # The level 7 is the highest level
    level: 5

    ignoreErrors:
        - '#Unsafe usage of new static#'

    excludes_analyse:
        - ./*/*/FileToBeExcluded.php

    checkMissingIterableValueType: false
```

### Misc

You may want to be aware of all the BC breaks detailed in:

- PHPStan changelog: [github.com/phpstan/phpstan/releases/tag/0.12.0](https://github.com/phpstan/phpstan/releases/tag/0.12.0)

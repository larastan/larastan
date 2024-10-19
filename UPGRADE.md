# Upgrade Guide

## Upgrading to `2.9.9` from `2.9.8`

Manager's `createXXXDriver()` return type is now taken into account when validating the accessibility of public methods of a Manager. This gives you the option to decide whether the `createXXXDriver()` method should have a contract or an implementation as a return type.

If you have a Manager that have a `createXXXDriver()` that returns a contract, only the contract's public methods will now be considered accessible. Before this release, the public methods of the class returned by the `createXXXDriver()` method were considered accessible.

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
 * @template TModelClass of \Illuminate\Database\Eloquent\Model
 * @extends Builder<TModelClass>
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

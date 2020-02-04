# Upgrade Guide

## Upgrading To 0.5.1 From 0.5.0

#### Eloquent Model property types
0.5.1 introduces ability to infer Eloquent model property types. To take advantage of this you have to remove any model class from `universalObjectCratesClasses` PHPStan configuration parameter, if you added any earlier.

#### Custom Eloquent Builders
If you are taking advantage of custom Eloquent Builders for your models, you have to mark your custom builder class as generic like so:
```php
/**
 * @template TModelClass
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

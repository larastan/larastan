# Features

All features that are specific to Laravel applications are listed here.

## Laravel 9 Attributes

In order for [Laravel 9 Attributes](https://laravel.com/docs/9.x/eloquent-mutators#accessors-and-mutators) to be recognized as model properties, they must be `protected` methods annotated with the `Attribute` Generic Types.

The first generic type is the getter return type, and the second is the setter argument type.

#### Examples

```php
<?php
/** @return Attribute<string[], string[]> */
protected function scopes(): Attribute
{
    return Attribute::make(
        get: fn (?string $value) => is_null($value) ? [] : explode(' ', $value),
        set: function(array $value) {
            $set = array_unique($value);
            sort($set);
            return ['scopes' => implode(' ', $set)];
        }
    );
}
```

```php
<?php
/** @return Attribute<bool, never> */
protected function isTrue(): Attribute
{
    return Attribute::make(
        get: fn (?string $value): bool => $value === null,
    );
}
```

## Custom Model Builders

Custom builders offer a better static analysis experience than using model scopes, and they help slim down the model class.

Here's an example of how to create a custom builder class:

```php
<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\HasBuilder;
use Illuminate\Database\Eloquent\Model;

/** @extends Builder<User> */
class UserBuilder extends Builder
{
    /** @return $this */
    public function active(): static
    {
        $this->where('active', true);

        return $this;
    }
}

class User extends Model
{
    /** @use HasBuilder<UserBuilder> */
    use HasBuilder;

    protected static string $builder = UserBuilder::class;
}

// Usage
$users = User::query()
        ->active()
        ->get();
```

> [!NOTE]
> The `HasBuilder` trait was introduced in Laravel 11, if you are using an older version of Laravel you can use the following:
>
> ```php
> <?php
> class User extends Model
> {
>    public static function query(): UserBuilder
>    {
>        return parent::query();
>    }
>
>    /** @param  \Illuminate\Database\Query\Builder  $query */
>    public function newEloquentBuilder($query): UserBuilder
>    {
>        return new UserBuilder($query);
>    }
> }
> ```

## Model Factories

Because the `Factory` class is generic, you need to specify the template type in your model factories.
And while Laravel has magic to automatically associate a factory with a model, you'll have a much better static analysis experience if you specify the factory class in the model.

So for example, here's how the classes can look:

```php
<?php

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected $model = User::class;
}

class User extends Model
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected static string $factory = UserFactory::class;
}
```

> [!NOTE]
> The `HasFactory` generics was introduced in Laravel 11, if you are using an older version of Laravel you can use the following:
>
> ```php
> <?php
> class User extends Model
> {
>    /**
>     * @param  (callable(array<string, mixed>, static|null): array<string, mixed>)|array<string, mixed>|int|null  $count
>     * @param  (callable(array<string, mixed>, static|null): array<string, mixed>)|array<string, mixed>  $state
>     */
>    public static function factory($count = null, $state = []): UserFactory
>    {
>        return parent::factory();
>    }
>
>    protected static function newFactory(): UserFactory
>    {
>        return UserFactory::new();
>    }
> }
> ```

## Custom Model Collections

Custom collections can be created to extend the functionality of the default collection class.

So for example, here's how the classes can look:

```php
<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\HasCollection;
use Illuminate\Database\Eloquent\Model;

/** @extends Collection<array-key, User> */
final class UserCollection extends Collection
{
}

class User extends Model
{
    /** @use HasCollection<UserCollection> */
    use HasCollection;

    protected static string $collectionClass = UserCollection::class;
}
```

Or if the collection is used for multiple models then you need to create a generic collection class
and then specify the template type in the model.

```php
<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\HasCollection;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TKey of array-key
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @extends Collection<TKey, TModel>
 */
class GeneralCollection extends Collection
{
}

class User extends Model
{
    /** @use HasCollection<GeneralCollection<int, static>> */
    use HasCollection;

    protected static string $collectionClass = GeneralCollection::class;
}
```

> [!NOTE]
> The `HasCollection` trait was introduced in Laravel 11, if you are using an older version of Laravel you can use the `newCollection` method to override the collection class:
>
> ```php
> <?php
> class User extends Model
> {
>     /**
>      * Create a new Eloquent Collection instance.
>      *
>      * @param  array<array-key, \Illuminate\Database\Eloquent\Model>  $models
>      * @return GeneralCollection<int, static>
>      */
>     public function newCollection(array $models = []): GeneralCollection
>     {
>         return new GeneralCollection($models);
>     }
> }
> ```

## FormRequest Type Inference

Set `checkFormRequestTypes` to infer types from a FormRequest's `rules()`
method. It is disabled by default:

```neon
parameters:
    checkFormRequestTypes: true
```

After validation succeeds, Larastan uses `rules()` as the source of truth for:

- magic properties such as `$request->name`;
- the full array and exact keyed values returned by `validated()`; and
- the generic `ValidatedInput` or selected array returned by `safe()`.

For example, these rules:

```php
public function rules(): array
{
    return [
        'name' => ['required', 'string'],
        'age' => ['sometimes', 'integer'],
    ];
}
```

produce the following types:

```php
$request->name;              // string
$request->age;               // int|numeric-string|null
$request->validated();       // array{name: string, age?: int|numeric-string}
$request->validated('age');  // int|numeric-string|null
$request->safe();            // ValidatedInput<array{name: string, age?: int|numeric-string}>
$request->safe(['name']);    // array{name: string}
```

Magic properties describe the original request input after validation.
Validation does not cast values, so rules such as `integer` retain the incoming
HTTP representations accepted by Laravel. Optional magic properties include
`null`, while optional validated values use optional array keys. Raw nested
arrays keep unvalidated keys unless an allowed-key rule seals them.
`validated()` and `safe()` use Laravel's default behavior of pruning
unvalidated nested keys for bare `array` and `list` rules. Allowed-key rules such
as `array:name,email` and `Rule::array(['name', 'email'])` retain submitted allowed
keys, including keys without child rules. Adding a separate bare `array` or
`list` rule restores pruning. Allowed keys remain optional unless another rule
requires them. When exclusion removes every child rule, Laravel can retain the
remaining parent input, so the inferred shape stays open.

Larastan only narrows magic properties after successful validation. They remain
`mixed` during request setup, preparation, authorization, validator
construction, validation callbacks, and failed-validation handling. Reads in
`passedValidation()`, ordinary FormRequest methods, and external consumers use
the inferred type. Native properties and explicit `@property`, `@property-read`,
or `@property-write` declarations always take precedence.
Aliases of `$this` within the lifecycle methods follow the same rules.

Larastan only refines `validated()` and `safe()` when the call resolves to
Laravel's original FormRequest method. Application overrides keep their
declared return type. `validated()` supports exact integer or string keys,
including dotted strings, together with default values. `safe()` supports no
argument, explicit `null`, or an exact array of string keys, including dotted
strings. Dynamic or unsupported key expressions keep Laravel's existing broad
return type. Methods, properties, and array offsets on the returned
`ValidatedInput` are not refined further.

A Closure default contributes its return type; other known defaults retain
their own types. Defaults typed only as `callable` or `object` stay `mixed`,
since they may be a Closure.
Root wildcard rules also keep inference broad because they can affect every
field, including otherwise exact entries.

### Rules and fallbacks

Larastan analyses conventional `rules()` methods that it can resolve statically.
Exact returned arrays, local variables, constant top-level spreads, and exact
PHPDoc array shapes can contribute rules. When there are multiple supported
return paths, only fields present on every path are inferred. Fields that depend
on dynamic rule assembly, loops, unresolvable helper results,
`parent::rules()` composition, or unsupported rules stay `mixed` or keep
Laravel's existing broad type. Other exact entries can still be inferred when
dynamic entries cannot overwrite them. Validated shapes remain unsealed when
additional dynamic or branch-specific keys may be returned. These fallbacks do
not produce a diagnostic.

Optional entries in a PHPDoc rule list keep the affected field broad: optional
modifiers can permit null, omit the field, or exclude its entire subtree.

Integer-backed enum rules infer backing integer values and `numeric-string`;
they do not guarantee canonical decimal strings. Enum inference follows the
feature's practical HTTP scalar approximation and does not enumerate every
boolean, float, or enum-object representation accepted by weak enum conversion.

`Rule::anyOf()` can accept associative arrays even when its alternatives name
only scalar rules. Its inferred type includes that array possibility. An outer
scalar rule still constrains the result. Numeric enum and AnyOf unions are
benevolent where possible, preserving ordinary scalar calls under PHPStan's
default settings. `checkBenevolentUnionTypes` enables stricter argument checking
for these unions.

This feature does not support:

- custom `validator()`, `getValidatorInstance()`,
  `createDefaultValidator()`, or `validationRules()` pipelines;
- rules added or replaced in `withValidator()` or another validator hook;
- values changed through `merge()`, `replace()`, direct input mutation, or
  `passedValidation()`;
- route parameters that contradict an optional rule-derived property type; or
- applications that globally enable Laravel's
  `includeUnvalidatedArrayKeys()` behavior.

`exists` and other database-membership rules are type-neutral because Laravel
does not cast the submitted value. Other rules must establish its type.
Larastan models ordinary HTTP request values, so string-like input types do not
include arbitrary `Stringable` objects injected by application code.

See [`composer.json`](../composer.json) for the supported dependency versions.
CI coverage is defined in the [GitHub Actions workflows](../.github/workflows/),
including the [test matrix](../.github/workflows/tests.yml) and the
[end-to-end projects](../.github/workflows/e2e-tests.yml).

## Model Properties

Larastan will automatically scan your application's migrations in order to infer the database schema and therefore it is able to infer the existence of magic properties on Eloquent model classes.

Various parameters can be set to [configure this behavior](custom-config-parameters.md#databasemigrationspath).

## Model Relationships

In order for Larastan to recognize Model relationships you are required to document the generic types of the relation class:

```php
/** @return BelongsTo<User, $this> */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

/** @return HasMany<Post, $this> */
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}
```

## Bootstrap Error Reporting (since 3.9.0)

Larastan boots your Laravel application during analysis. If that bootstrap fails, Larastan can print a
 beautifully styled error report with a clear title, useful tips to resolve the issue and a stack trace. Depending on if the error is coming from the framework itself or from user code, it provides different tips and messages. The output respects `--ansi`
and `--no-ansi` flags.

![Screenshot of a failed PHPStan analysis showcasing the custom styled error.](/docs/framework-bootstrap-error.png)

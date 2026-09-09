# Custom PHPDoc types

All custom types that are specific to Larastan are listed here. Types that are defined by PHPStan
can be found on [their website](https://phpstan.org/writing-php-code/phpdoc-types).


## view-string

The `view-string` type is a subset of the `string` type. Any `string` that passes the `view()->exists($string)` test
is also a valid `view-string`.

**Example:**

```php
/**
 * @phpstan-param view-string $view
 * @param string $view
 * @return \Illuminate\View\View
 */
public function renderView(string $view): View
{
    return view($view);
}
```
Now, whenever you call `renderView`, Larastan will try to check whether
the given string is a valid blade view.


If the string is not an existing blade view, the following error will be displayed by Larastan.
```
Parameter #1 $view of method TestClass::renderView() expects view-string, string given.
```

Larastan also checks the `view`, `html`, `text`, and `markdown` constructor arguments of
`Illuminate\Mail\Mailables\Content`, including named arguments. These arguments accept a
view name or `null`. The `htmlString` argument contains rendered HTML and is not checked
as a view name.

When working with packages, all vendor-prefixed paths like `acme::example` may fail. As packages don't contain a Laravel app, the default skeleton from `orchestra/testbench` is used. This instance doesn't know about the package so views are not registered. Create a `testbench.yaml` file to [register](https://packages.tools/testbench#package-service-providers) your service provider to solve this issue.

```yaml
providers:
    - Acme\AcmeServiceProvider
```

## model-property
`model-property` extends the built-in `string` type and acts like a string in the type level. But during the analysis if Larastan finds that an argument of the method or a function has a `model-property<ModelName>`, it'll try to check that the given argument value is actually a property of the model.

All of the Laravel core methods have this type thanks to the stubs. So whenever you use a Eloquent builder, relation or a model method that expects a column, it'll be checked by Larastan if the column actually exists. But you can also typehint any argument with `model-property` in your code.

The actual check is done by the `ModelPropertyRule`. You can read the details [here](rules.md#ModelPropertyRule).

## collection-of

The `collection-of<Model>` type resolves to the appropriate collection class for a given Eloquent model. This is useful when you want to type-hint that a method returns or accepts a collection of specific model instances.

Larastan automatically determines the correct collection type:
- If the model has a custom collection (via `newCollection()` method or `CollectedBy` attribute), it resolves to that collection
- Otherwise, it resolves to `Illuminate\Database\Eloquent\Collection<(int|string), Model>`

**Example:**

```php
use App\User;
use App\Post;
use Illuminate\Database\Eloquent\Collection;

/**
 * @phpstan-return collection-of<User>
 */
function getActiveUsers(): Collection
{
    return User::where('active', true)->get();
}

/**
 * @phpstan-param collection-of<Post> $posts
 */
function publishPosts(Collection $posts): void
{
    $posts->each(fn ($post) => $post->publish());
}
```

If `User` has a custom `UserCollection`, `collection-of<User>` will resolve to `UserCollection<(int|string), User>`.
If `Post` uses the standard Eloquent collection, `collection-of<Post>` will resolve to `Illuminate\Database\Eloquent\Collection<(int|string), Post>`.

Generic collection keys use a benevolent `int|string` union, allowing compatibility with integer
and string keys. Collections with fixed key or model types retain their declared types.

**Template Support:**

The `collection-of` type also works with generic templates:

```php
/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class ModelRepository
{
    /**
     * @phpstan-param class-string<TModel> $modelClass
     * @phpstan-return collection-of<TModel>
     */
    public function findAll(string $modelClass): Collection
    {
        return $modelClass::all();
    }
}
```

## builder-of

The `builder-of<Model>` type resolves to the appropriate Eloquent builder class for a given Eloquent model.

Larastan automatically determines the correct builder type:
- If the model has a custom builder (via `newEloquentBuilder()` method or `UseEloquentBuilder` attribute), it resolves to that builder class
- Otherwise, it resolves to `Illuminate\Database\Eloquent\Builder<Model>`

**Example:**

```php
use App\User;
use App\Post;
use Illuminate\Database\Eloquent\Builder;

/**
 * @phpstan-return builder-of<User>
 */
function getActiveUsers(): Builder
{
    return User::query()->where('active', true);
}

/**
 * @phpstan-param builder-of<Post> $postQuery
 */
function publishPosts(Builder $postQuery): void
{
    $postQuery->where('foo', 'bar')->get()->each(fn ($post) => $post->publish());
}
```

**Template Support:**

The `builder-of` type preserves `static`, `$this`, generic model arguments, and intersections. For a union of models, it produces a union of their builder types.

Larastan uses it for model query methods, `Collection::toQuery()`, and relation query builders, so these methods retain custom builders.

The `builder-of` type also works with generic templates:

```php

use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
class ModelRepository
{
    /**
     * @phpstan-param class-string<TModel> $modelClass
     * @phpstan-return builder-of<TModel>
     */
    public function getQuery(string $modelClass): Builder
    {
        return $modelClass::query();
    }
}
```


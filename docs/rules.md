# Rules

All rules that are specific to Laravel applications
are listed here with their configurable options.

## FormRequest diagnostics

These rules are enabled together with [FormRequest type inference](features.md#formrequest-type-inference):

```neon
parameters:
    checkFormRequestTypes: true
```

The existing parameter defaults to `false`. There are no separate rule switches.
Each diagnostic can be suppressed using its identifier, for example:

```php
// @phpstan-ignore larastan.formRequest.prematureValidatedAccess
$this->validated();
```

### Unknown validated keys

Identifier: `larastan.formRequest.unknownValidatedKey`.

Reports a constant key that cannot occur in the request's inferred validated data:

```php
// The request validates 'email', not 'emali'.
$request->validated('emali', false);
$request->safe(['emali']);
$request->safe()->only(['emali']);
```

An explicit default does not suppress the diagnostic. Optional fields are valid
keys; a missing validation rule is different from an optional input value.
Ordinary dotted paths and numeric array indices are supported. Named arguments
and positional string selectors in `safe()->only('email', 'profile.name')` are
also supported.

For request unions, PHPStan's existing `checkUnionTypes` setting controls whether
a key absent from only some request types is reported. When it is `false`, every
member must prove absence. Unknown shapes and application overrides never count
as proof of absence.

The rule uses the existing validated-data projection and its assumptions about
ordinary FormRequest validation and Laravel's default validated-array filtering.
It does not model custom validation pipelines or `includeUnvalidatedArrayKeys()`.
Dynamic or multi-valued keys, partially known selector lists, wildcard and
`{first}` / `{last}` selectors, array-form `validated()` paths, and unpacked
arguments are skipped. Open or unknown data stays conservative, although a known
nested subtree can still prove a missing child.

Only Laravel's original `validated()` and `safe()` methods provide evidence.
The `only()` check is limited to an immediate `safe()` or `safe(null)` result:
general `ValidatedInput` variables, mutation chains, and `except()` are not checked.

### Premature validated-data access

Identifier: `larastan.formRequest.prematureValidatedAccess`.

Reports direct `$this->validated()` and `$this->safe()` calls in
`prepareForValidation()`, `authorize()`, `rules()`, `validationData()`, `messages()`,
`attributes()`, `withValidator()`, and the `after()` registration method. Laravel
normally initializes the request validator later. Use `input()` for unvalidated
data, or move work requiring validated data to `passedValidation()` or the controller.

This is lexical guidance about the normal lifecycle, not initialization tracking.
Custom validator setup and guards do not exempt a call; use the identifier when
deliberate custom initialization makes the warning inapplicable. Aliases, other
receivers, helper methods, closures (including immediately invoked closures),
arrow functions, static calls, and application method overrides are not checked.
Skipping a callback does not guarantee that accessing validated data there is safe.

A premature call with an unknown key can report both diagnostics independently.

### Simple validation-rule pairs

These checks inspect statically known returned rule maps in FormRequest `rules()`
methods. They cover plain top-level fields with known built-in string rules, in
pipe-delimited strings or rule arrays.

- `larastan.formRequest.requiredNullable`: `required|nullable` still rejects null.
  Decide whether null should be valid for the field. This is guidance, not advice
  to remove `nullable` automatically: it can affect other validation errors and
  callbacks.
- `larastan.formRequest.requiredMissing`: `required|missing` requires the field to
  be both present and absent. Choose one. If `nullable` is also present, only this
  contradiction is reported.

Dynamic or optional declarations, custom rules, objects, closures, conditional
presence rules, `sometimes`, and exclusion rules are skipped. Dotted, escaped,
and numeric field names are not checked, nor are parents with child rules. A
wildcard anywhere in the returned map skips these pair checks for that map.
Separate return sites are checked independently; alternative rule values are not
combined into a conflict.

## NoModelMake

Checks for calls to the static method `make()` on subclasses of `Illuminate\Database\Eloquent\Model`.
While its usage does not result in an error, unnecessary work is performed and the
model is needlessly instantiated twice. Simply using `new` is more efficient.

### Examples

```php
User::make()
```

Will result in the following error:

```
Called 'Model::make()' which performs unnecessary work, use 'new Model()'.
```

### Configuration

This rule is enabled by default.
To disable, add the following to your `phpstan.neon` file:

```neon
parameters:
    noModelMake: false
```

## NoImplicitQueryBuilderCall

Reports static and instance calls on Eloquent models that Laravel forwards to a
query builder or local scope. Use an explicit builder entry point to make these
queries visible at the call site.

### Examples

```php
User::where('active', true)->get();
$user->active()->get();
```

For `App\User::where()`, the rule reports:

```text
Call to static method App\User::where() is forwarded to the query builder.
    💡 Use App\User::query()->where() instead.
```

Use `query()` for static calls and `newQuery()` for instance calls:

```php
User::query()->where('active', true)->get();
$user->newQuery()->active()->get();
```

The rule supports automatic fixes, including null-safe instance calls.

The rule covers Eloquent and query builder methods, custom builders, dynamic
`where*` methods recognized by Larastan, legacy `scopeActive()` methods invoked
as `active()`, and inaccessible methods marked with `#[Scope]`.

Real model methods, including `all()`, `with()`, `save()`, and user-defined or
inherited methods with builder method names, are excluded. Accessible scope
methods called directly with a builder are also excluded, including public
attribute scopes. Undefined methods remain PHPStan's responsibility. Receivers
with multiple possible model classes or a model-or-builder union are skipped.

### Configuration

This rule is disabled by default. To enable it in `phpstan.neon`:

```neon
parameters:
    noImplicitQueryBuilderCall: true
```

Error identifier: `larastan.noImplicitQueryBuilderCall`.

## NoUnnecessaryCollectionCall

Checks for method calls on instances of `Illuminate\Support\Collection` and their
subclasses. If the same result could have been determined
directly with a query then this rule will produce an error.
This rule exists to reduce unnecessarily heavy queries on the database
and to prevent unneeded loops over Collections.

### Examples

```php
User::all()->count();
$user->roles()->pluck('name')->contains('a role name');
```

Will result in the following errors:
```
Called 'count' on Laravel collection, but could have been retrieved as a query.
Called 'contains' on Laravel collection, but could have been retrieved as a query.
```

To fix the errors, the code in the previous example could be changed to:
```php
User::count();
$user->roles()->where('name', 'a role name')->exists();
```

### Configuration

This rule is enabled by default.
To disable, add the following to your `phpstan.neon` file:

```neon
parameters:
    noUnnecessaryCollectionCall: false
```

You can also configure the collection methods which this rule
checks for. By default, all collection methods are checked.
To only enable a specific set of methods, you could set the
`noUnnecessaryCollectionCallOnly` configuration key. For example:
```neon
parameters:
    noUnnecessaryCollectionCallOnly: ['count', 'first']
```
will only throw errors on the `count` and `first` methods.
The inverse is also configurable, to not throw an exception
on the `contains` method, one could set the following value:
```neon
parameters:
    noUnnecessaryCollectionCallExcept: ['contains']
```

## NoUnnecessaryEnumerableToArrayCalls

This rule checks for unnecessary calls `Enumerable::toArray()` that
could have used `all()` instead. The `toArray()` method recursively
converts all Arrayable items in the Enumerable to an array and if
none of the items are Arrayable, it is unnecessary map call.

### Examples

```php
collect([1, 2, 3])->toArray();
```

Will result in the following error:

```
Called [toArray()] on an Enumerable which does not contain any Arrayables.
```

To fix the error, the code in the previous example could be changed to:

```php
collect([1, 2, 3])->all();
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    noUnnecessaryEnumerableToArrayCalls: true
```

## ModelPropertyRule

---

**NOTE**: This rule is currently in beta! If you want to improve its analysis, you can check out the issue [here](https://github.com/larastan/larastan/issues/676) and contribute!

---

**default**: false

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkModelProperties: true
```

This rule checks every argument of a method or a function, and if the argument has the type `model-property`, it will try to check the given value against the model properties. And if the model does not have the given property, it'll produce an error.

### Basic example

```php
User::create([
    'name' => 'John Doe',
    'emaiil' => 'john@example.test'
]);
```

Here we have a typo in `email` column. So if we run analysis on this file Larastan will generate the following error:

```
Property 'emaiil' does not exist in App\User model.
```

This check will be done automatically on Laravel's core methods where a property is expected. But you can also typehint the `model-property` in your own code to take advantage of this analysis.

You can define a function like this:
```php
/**
 * @phpstan-param model-property<\App\User> $property
 */
function takesOnlyUserModelProperties(string $property)
{
    // ...
}
```

And if you call the function above with a property that does not exist in User model, Larastan will warn you about it.

```php
// Property 'emaiil' does not exist in App\User model.
takesOnlyUserModelProperties('emaiil');
```

## OctaneCompatibilityRule

This is an optional rule that can check your application for Laravel Octane compatibility.
You can read more about why in [the official Octane docs](https://laravel.com/docs/octane#dependency-injection-and-octane).

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkOctaneCompatibility: true
```

### Examples

Following code
```php
public function register()
{
    $this->app->singleton(Service::class, function ($app) {
        return new Service($app);
    });
}
```
Will result in the following error:

`Consider using bind method instead or pass a closure.`

## RelationExistenceRule

This rule checks relation names on Eloquent models, builders, and relations. It also checks `load*` calls on Eloquent collections and model `$with` and `$withCount` defaults.

Supported methods are:

- `has`, `orHas`, `doesntHave`, `orDoesntHave`, and their `*Morph` variants
- `whereHas`, `orWhereHas`, `whereDoesntHave`, `orWhereDoesntHave`, and their `*Morph` variants
- `whereRelation`, `orWhereRelation`, `whereDoesntHaveRelation`, `orWhereDoesntHaveRelation`, and their `*MorphRelation` / `*MorphDoesntHaveRelation` variants
- `withWhereHas`, `withWhereRelation`, `with`, `withOnly`, `load`, and `loadMissing`
- `withAggregate`, `withCount`, `withMax`, `withMin`, `withSum`, `withAvg`, `withExists`, and the corresponding `load*` methods

Eager loading supports dotted paths, constraint arrays, nested arrays, and column selectors such as `accounts.transactions:id`. Aggregate methods and `$withCount` accept aliases such as `accounts as total`; dotted aggregate relation names are not supported by Laravel.

Inherited defaults are checked against each concrete model, so a child can provide a relation named in an abstract parent's defaults. Defaults are read statically, without constructing models. Dynamic names and related model types that cannot be resolved are skipped.

This rule is not optional.

### Examples

For the following code:
```php
\App\User::query()->has('foo');
\App\Post::query()->has('users.transactions.foo');
```

Larastan will report two errors:
```
Relation 'foo' is not found in App\User model.
Relation 'foo' is not found in App\Transaction model.
```
## CheckDispatchArgumentTypesCompatibleWithClassConstructorRule

This rule will check if your job dispatch argument types are compatible with the constructor of the job class.

### Examples

Assume the following job:

```php
final class ExampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $foo,
        protected string $bar,
    ) {}

    // Rest of the job class
}
```

Dispatching the job with the following examples:

```php
ExampleJob::dispatch(1);
ExampleJob::dispatch('bar', 1);
```

will result in the following errors:

```
Job class ExampleJob constructor invoked with 1 parameter in ExampleJob::dispatch(), 2 required.
Parameter #1 $foo of job class ExampleJob constructor expects int in ExampleJob::dispatch(), string given.
Parameter #2 $bar of job class ExampleJob constructor expects string in ExampleJob::dispatch(), int given.
```

## NoUselessValueFunctionCallsRule

This rule will check if unnecessary calls to the `value()` function are made.

### Examples

Calling the following functions:

```php
$foo = value('foo');
$bar = value(true);
```

will result in errors:

```
Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything
Calling the helper function 'value()' without a closure as the first argument simply returns the first argument without doing anything
```

## NoUselessWithFunctionCallsRuleTest

This rule will check if unnecessary calls to the `with()` function are made.

### Examples

Calling the following functions:

```php
$foo = with('foo');
$bar = with('bar', null);
```

will result in errors:

```
Calling the helper function 'with()' with only one argument simply returns the value itself. if you want to chain methods on a construct, use '(new ClassName())->foo()' instead
Calling the helper function 'with()' without a closure as the second argument simply returns the value without doing anything
```

## DeferrableServiceProviderMissingProvidesRule

This rule will check for a missing `provides` method in deferrable `ServiceProvider`s.

### Examples

A correct `DeferrableProvider` returns an `array` of `string`s or `class-string`s in the 'provides' method:

```php
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class CorrectDeferrableProvider extends ServiceProvider implements DeferrableProvider
{
    public function register() {}

    public function provides(): array
    {
        return [
            'foo',
            'bar',
        ];
    }
}
```

When the method is not present, the ServiceProvider will not be used.

```php
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class IncorrectDeferrableProvider extends ServiceProvider implements DeferrableProvider
{
    public function register() {}
}
```

This will result in the following error:

```
ServiceProviders that implement the "DeferrableProvider" interface should implement the "provides" method that returns an array of strings or class-strings
```

## UnusedViewsRule

This rule will find any unused views in your application.

> **NOTE**: Due to the nature of static analysis, this rule can produce false positives. It cannot find every usage of a view, so it is possible that a view is reported as unused when it is actually used. This is why it's an optional rule.

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkUnusedViews: true
```

This rule analyzes your view files to find used views. By default, it checks the `resources/views` directory for Blade files. But if you have views in other directories you can use `viewDirectories` config option to specify them. For example:

```neon
parameters:
    checkUnusedViews: true
    viewDirectories:
        - domainA/resources/views
        - a/path/to/views
```

### Supported View Usages

- `view` helper function.
- `$this->markdown` and `$this->view` methods in Mailables.
- `Illuminate\View\Factory::make` method.
- `Illuminate\Support\Facades\View::make` method.
- `Illuminate\Support\Facades\Route::view` method.
- `@extends` Blade directive.
- `@include` Blade directive.
- `@includeIf` Blade directive.
- `@includeUnless` Blade directive.
- `@includeWhen` Blade directive.
- `@includeFirst` Blade directive.

## NoMissingTranslationsRule

This rule will find any untranslated strings in your application. It is primarily meant for applications that make use of the dot syntax like `messages.greet`. If you're using translation strings as keys, this rule may be unnecessary. Enabling this rule may decrease performance as it will scan the available views and translations.

Translations from vendors like `vendor::key` will not be checked.

> **NOTE**: If you store your translations in a database, this rule will not be able to detect them. You should leave this rule disabled in such cases.

### Examples

For the following code:
```php
__('messages.greet')
```

Larastan may report the following error:
```
Translation "messages.greet" has not been found.
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkMissingTranslations: true
```

By default, the path `resources/lang` is scanned. If you have translations elsewhere, make sure to register all the paths.

```neon
parameters:
    checkMissingTranslations: true
    translationDirectories:
        - resources/lang
        - resources/translations
```

## NoEnvCallsOutsideOfConfig

Checks for `env` calls outside the `config` directory, which return `null` when the config is cached.

### Examples

Suppose this calls happens somewhere in your code outside the `config` directory:

```php
env('APP_ENV')
```

It will result in the following error:

```
Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.")
```

Use the corresponding configuration option instead:

```php
config('app.env')
```

### Configuration

This rule is enabled by default.
To disable, add the following to your `phpstan.neon` file:

```neon
parameters:
    noEnvCallsOutsideOfConfig: false
```

By default, this rule checks for env calls outside the application config directory. If your configuration files are stored elsewhere, you can use the configDirectories option to specify them.

```neon
parameters:
    configDirectories:
        - src/config
        - tests
```

## ModelAppendsRule

Checks model's `$appends` property for computed properties. The properties added to `$appends` array should both exist in the model and be computed properties.

### Examples

```php
class User extends \Illuminate\Database\Eloquent\Model
{
    protected $appends = ['email'];
}
```

Now if you were to call `toArray` or `toJson` methods on an instance of User class, you'd expect to see the `email` there. But in reality it'd be `null` This rule prevents you from that mistake. So you'd get the following error:

```
Property 'email' is not a computed property, remove from $appends.
```

### Configuration

This rule is enabled by default.
To disable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkModelAppends: false
```

## NoPublicModelScopeAndAccessorRule

Ensures Eloquent model local query scopes and attribute accessors are not part of the public API. 
Local scopes and attribute accessors should be declared `protected`.

### Examples

Public local scope method:

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // ❌ Should be protected
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
```

Will result in the following error:

```
Local query scope method 'scopeActive' should be declared as protected.
```

Public accessor returning `Attribute`:

```php
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // ❌ Should be protected
    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['first_name'].' '.$attributes['last_name'],
        );
    }
}
```

Will result in the following error:

```
Model accessor method 'fullName' should be declared as protected.
```

Fix by changing the visibility to `protected` in both cases.

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkModelMethodVisibility: true
```

## NoAuthFacadeInRequestScopeRule and NoAuthHelperInRequestScopeRule

These rules will warn you if you are using `Auth::check()`, `Auth::user()`, `Auth::guest()`, `auth()->check()`, `auth()->user()`, or `auth()->guest()` while you have access to the request already in your current scope with `$request` variable. So it should only warn if there is a variable named `$request` in the current scope with `Illuminate\Http\Request` type (or any child class).

### Examples

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyController
{
    public function __invoke(Request $request)
    {
        if (Auth::check()) {
            //
        }
    }
}
```

Will result in the following error:

```
Do not use Auth::check() in a class that has access to the request. Use $request->user() !== null instead.
```

You can fix this by using the `$request` variable directly:

```php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyController
{
    public function __invoke(Request $request)
    {
        if ($request->user() !== null) {
            //
        }
    }
}
```

### Configuration

This rule is disabled by default.  To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkAuthCallsWhenInRequestScope: true
```

## ConfigCollectionRule

This rule checks for incorrect keys passed into the `Config::collection` method. It helps to prevent runtime errors when a configuration key that is not an array is used.

### Examples

Given a configuration file `config/foo.php` with the following content:
```php
return [
    'foo' => 'bar',
    'bar' => [1, 2, 3],
];
```

The following code would produce an error:
```php
$collection = Config::collection('foo.foo');
```

```
Config key 'foo.foo' is not an array.
```

To fix this, you should use a config key that returns an array:
```php
$collection = Config::collection('foo.bar');
```

### Configuration

This rule is disabled by default. To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkConfigTypes: true
```

## UniqueJobDeclaresUniqueForRule

Every job implementing `Illuminate\Contracts\Queue\ShouldBeUnique` (including
`ShouldBeUniqueUntilProcessing`, which extends it) must declare `uniqueFor`, either as a
property (`public int $uniqueFor = 3600;`) or a method (`public function uniqueFor(): int`).

Without `uniqueFor` the uniqueness lock is held until the job finishes processing. If a
worker dies mid job (OOM, deploy, fatal) the lock is never released and the job can never be
dispatched again until the cache key is cleared by hand. `uniqueFor` bounds the lock so a
stuck job self heals after the timeout.

Abstract classes are skipped. They aren't dispatched directly, and a concrete subclass
supplies (or inherits) `uniqueFor`.

### Examples

```php
class FetchSocialAvatar implements ShouldQueue, ShouldBeUnique
{
    public function uniqueId(): string
    {
        return (string) $this->userId;
    }
}
```

Will result in the following error:

```
Job App\Jobs\FetchSocialAvatar implements ShouldBeUnique but does not declare uniqueFor.
💡 Declare a $uniqueFor property or a uniqueFor() method.
```

To fix the error, declare how long the lock may live:

```php
class FetchSocialAvatar implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }
}
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkUniqueJobUniqueFor: true
```

## UniqueJobDeclaresUniqueIdRule

A **parameterized** `ShouldBeUnique` job, meaning one whose constructor takes arguments,
must declare `uniqueId`: a method (`public function uniqueId(): string`) or a property
(`public $uniqueId`).

Laravel builds the lock key as `laravel_unique_job:<class>:<uniqueId>` and falls back to an
empty `uniqueId` when neither is declared (`Illuminate\Bus\UniqueLock::getKey`). For a
parameterized job the empty key collapses *every* dispatch into one unique job regardless of
its arguments, so legitimately distinct jobs (per company, per product, ...) are silently
dropped at dispatch with no error. That lost work failure is harder to spot than a leaked
lock.

The rule fires only when the constructor has at least one parameter: a parameterless job is
a legitimate singleton whose class name only key is correct. A job that is intentionally
class wide satisfies the rule by declaring `uniqueId()` returning a constant, which makes
that intent explicit. Abstract classes are skipped.

### Examples

```php
class SyncCompany implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId)
    {
    }
}
```

Will result in the following error:

```
Unique job App\Jobs\SyncCompany has constructor parameters but does not declare uniqueId.
💡 Declare a uniqueId() method or a $uniqueId property to identify distinct jobs.
```

To fix the error, scope the lock to the arguments that make the job distinct:

```php
class SyncCompany implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }
}
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkUniqueJobUniqueId: true
```

## NoBatchedUniqueJobRule

A `ShouldBeUnique` job must not be dispatched through the bulk or batch entry points:
`Bus::batch([...])`, `Bus::bulk([...])` or the equivalent `Queue::bulk([...])`. Both bypass
the per job uniqueness guarantee:

- `Queue::bulk()` and `Bus::bulk()` push raw payloads straight onto the queue, skipping the
  dispatcher path that acquires the unique lock, so duplicates are queued and
  `ShouldBeUnique` silently does nothing.
- Batching a unique job means a duplicate is dropped at dispatch, but the batch's job count
  is computed up front, so the batch's progress and `then`/`finally` callbacks never
  reconcile and the batch can hang as pending.

Dispatch unique jobs individually (`Foo::dispatch(...)`). The rule recurses into nested
arrays (chains within a batch) and reports each offending job.

### Examples

```php
Bus::batch([
    new SyncCompany(1),
    new RegularJob(),
]);
```

Will result in the following error:

```
Unique job App\Jobs\SyncCompany is dispatched via batch().
💡 Dispatch unique jobs individually to preserve uniqueness.
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    noBatchedUniqueJob: true
```

## JobWithModelPropertyDeclaresSerializesModelsRule

A queued job (one implementing `Illuminate\Contracts\Queue\ShouldQueue`) that holds an
Eloquent model in a **public** property must use the `Illuminate\Queue\SerializesModels`
trait.

A queued job is serialized to the queue store at dispatch and unserialized in the worker.
Without `SerializesModels` an Eloquent model property is serialized whole: the full attribute
set, loaded relations and casts go onto the wire, bloating the payload, and the job runs
against a frozen snapshot taken at dispatch time, so any change made between dispatch and
execution is silently lost. `SerializesModels` instead stores just the class name and primary
key (plus the loaded relation names) and re-resolves the model fresh from the database when
the job runs, keeping the payload small and the data current. A model that was deleted in the
meantime then surfaces as a `ModelNotFoundException` instead of the job operating on stale
data.

The rule checks public properties, including inherited properties and nullable model types.
Protected and private properties are also serialized by PHP, but are outside this rule's scope.
An inherited `SerializesModels` (used by the class, a parent, or another trait) satisfies the
rule. Abstract classes are skipped; their public model properties are checked on concrete jobs.

### Examples

```php
class SendInvoice implements ShouldQueue
{
    public function __construct(public Invoice $invoice)
    {
    }

    public function handle(): void
    {
    }
}
```

Will result in the following error:

```
Job App\Jobs\SendInvoice has model properties ($invoice) but does not use SerializesModels.
💡 Use the Illuminate\Queue\SerializesModels trait.
```

To fix the error, let the queue store the model as a class and id reference:

```php
class SendInvoice implements ShouldQueue
{
    use SerializesModels;

    public function __construct(public Invoice $invoice)
    {
    }

    public function handle(): void
    {
    }
}
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkJobSerializesModels: true
```

## BatchedJobIsBatchableRule

Every job dispatched through `Bus::batch([...])` must use the `Illuminate\Bus\Batchable`
trait. The batch wires each job back to its parent batch so the job can read progress and
short circuit (`$this->batch()->cancelled()`), and so the batch can reconcile its job count
and fire `then`/`catch`/`finally`. All of that lives in `Batchable`. A job added to a batch
without it has no `batch()` method, so `$this->batch()` is a fatal call to an undefined method
the moment the job touches it. The framework does not validate this at dispatch, so the
breakage only surfaces in the worker.

The rule inspects the array literal passed to `Bus::batch()` and flags every element that is
a queued job but does not use `Batchable`, recursing into nested arrays (chains within a
batch).

### Examples

```php
Bus::batch([
    new BatchableJob(),
    new RegularJob(),
]);
```

Will result in the following error:

```
Job App\Jobs\RegularJob is batched but does not use Batchable.
💡 Use the Illuminate\Bus\Batchable trait.
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkBatchedJobIsBatchable: true
```

## BatchableJobChecksCancellationRule

A queued job that uses the `Illuminate\Bus\Batchable` trait must respect early batch
cancellation, either by checking `$this->batch()?->cancelled()` at the start of `handle()`, or
by registering the `Illuminate\Queue\Middleware\SkipIfBatchCancelled` middleware from
`middleware()`.

Cancelling a batch (`$batch->cancel()`, or the automatic cancel on first failure when the
batch is not `allowFailures`) only stops *future* dispatches from running their body. Laravel
does not forcibly kill jobs already on the queue: each queued job still wakes up and, unless
it checks `cancelled()`, runs its full body. That is wasted work at best, and at worst it
keeps mutating state (writing files, calling external APIs, charging cards) for a batch the
caller has already abandoned.

Every concrete job is checked using its effective methods, including methods inherited from
parents and traits. A `cancelled()` call or a `SkipIfBatchCancelled` reference satisfies the
check. Overriding a guarded method requires the subclass to provide its own check or middleware.
Abstract classes are skipped.

### Examples

```php
class GenerateReport implements ShouldQueue
{
    use Batchable;

    public function handle(): void
    {
        // ... heavy work ...
    }
}
```

Will result in the following error:

```
Batchable job App\Jobs\GenerateReport does not check for batch cancellation.
💡 Check $this->batch()?->cancelled() or use the SkipIfBatchCancelled middleware.
```

To fix the error, guard the work at the start of `handle()`:

```php
class GenerateReport implements ShouldQueue
{
    use Batchable;

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // ... heavy work ...
    }
}
```

Or let the skip middleware short circuit cancelled batches:

```php
class GenerateReport implements ShouldQueue
{
    use Batchable;

    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

    public function handle(): void
    {
        // ... heavy work ...
    }
}
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkBatchableJobChecksCancellation: true
```

## JobDispatchedInTransactionUsesAfterCommitRule

A queued job dispatched inside a `DB::transaction(...)` closure must defer its dispatch until
the transaction commits, by chaining `->afterCommit()` on the dispatch, declaring
`public bool $afterCommit = true;`, or implementing `ShouldQueueAfterCommit` on the job.
The last `afterCommit()` or `beforeCommit()` call in a dispatch chain takes precedence over the
property default. Before Laravel added support for overriding `ShouldQueueAfterCommit`, that
contract always deferred dispatch.

A queued job pushed during an open transaction can be picked up by a worker before the
transaction commits (a fast worker racing the still open connection): it then loads rows that
aren't visible yet and fails, or operates on half written state. If the transaction rolls back,
the job still runs against data that never existed. `afterCommit` holds the dispatch until the
outermost transaction commits and drops it entirely on rollback.

Only the `DB::transaction(Closure)` and arrow function form is inspected, since the manual
`beginTransaction()` ... `commit()` form has no closure to bound the analysis. Only the
chainable dispatch forms are flagged: `Job::dispatch(...)` and the `dispatch(new Job)` helper.
Synchronous dispatch (`dispatchSync`, `dispatch_sync`) and the `Bus` and `Queue` facade entry
points are left alone, as are non queued dispatchables, which run synchronously. The rule
assumes the default queue config: a project that enables `after_commit` globally does not need
it.

### Examples

```php
DB::transaction(function () use ($product) {
    $product->save();

    NotifyOwner::dispatch($product->id);
});
```

Will result in the following error:

```
Job App\Jobs\NotifyOwner is dispatched inside a transaction without deferring until commit.
💡 Call afterCommit() on the dispatch or implement ShouldQueueAfterCommit.
```

To fix the error, defer the dispatch explicitly:

```php
DB::transaction(function () use ($product) {
    $product->save();

    NotifyOwner::dispatch($product->id)->afterCommit();
});
```

Or opt the job in for every dispatch:

```php
class NotifyOwner implements ShouldQueue
{
    public bool $afterCommit = true;
}
```

### Configuration

This rule is disabled by default.
To enable, add the following to your `phpstan.neon` file:

```neon
parameters:
    checkDispatchInTransactionAfterCommit: true
```

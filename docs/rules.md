# Rules

All rules that are specific to Laravel applications 
are listed here with their configurable options.

## NoModelMake

Checks for calls to the static method `make()` on subclasses of `Illuminate\Database\Eloquent\Model`.
While its usage does not result in an error, unnecessary work is performed and the
model is needlessly instantiated twice. Simply using `new` is more efficient.

#### Examples

```php
User::make()
```

Will result in the following error:

```
Called 'Model::make()' which performs unnecessary work, use 'new Model()'.
```

#### Configuration

This rule is enabled by default. To disable it completely, add:

```neon
parameters:
    noModelMake: false
```

to your `phpstan.neon` file.

## NoUnnecessaryCollectionCall

Checks for method calls on instances of `Illuminate\Support\Collection` and their 
subclasses. If the same result could have been determined 
directly with a query then this rule will produce an error.
This rule exists to reduce unnecessarily heavy queries on the database 
and to prevent unneeded loops over Collections.

#### Examples
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

#### Configuration
This rule is enabled by default. To disable it completely, add:
```
parameters:
    noUnnecessaryCollectionCall: false
```
to your `phpstan.neon` file.

You can also configure the collection methods which this rule
checks for. By default, all collection methods are checked.
To only enable a specific set of methods, you could set the
`noUnnecessaryCollectionCallOnly` configuration key. For example:
```
parameters:
    noUnnecessaryCollectionCallOnly: ['count', 'first']
```
will only throw errors on the `count` and `first` methods.
The inverse is also configurable, to not throw an exception
on the `contains` method, one could set the following value:
```
parameters:
    noUnnecessaryCollectionCallExcept: ['contains']
```

## ModelPropertyRule

---

**NOTE**: This rule is currently in beta! If you want to improve it's analysis you can check out the issue [here](https://github.com/nunomaduro/larastan/issues/676) and contribute!

---

**default**: false

### Configuration
This rule is disabled by default. You can enable it by putting
```
parameters:
    checkModelProperties: true
```
to your `phpstan.neon` file.

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

This rule is disabled by default. You can enable it by adding
```
parameters:
    checkOctaneCompatibility: true
```
to your `phpstan.neon` file.

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

This rule will check if the given relations to some Eloquent builder methods exists. It also supports nested relations.

Supported Eloquent builder methods are:
- `has`
- `orHas`
- `doesntHave`
- `orDoesntHave`
- `whereHas`
- `orWhereHas`
- `whereDoesntHave`
- `orWhereDoesntHave`

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

## Examples

Given the following job:
```php
class ExampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var int */
    protected $foo;
    
    /** @var string */
    protected $bar;
    
    public function __construct(int $foo, string $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }

    // Rest of the job class
}
```
dispatching the job with the following examples
```php
ExampleJob::dispatch(1);
ExampleJob::dispatch('bar', 1);
```
will result in errors:
```
Job class ExampleJob constructor invoked with 1 parameter in ExampleJob::dispatch(), 2 required.
Parameter #1 $foo of job class ExampleJob constructor expects int in ExampleJob::dispatch(), string given.
Parameter #2 $bar of job class ExampleJob constructor expects string in ExampleJob::dispatch(), int given.
```

## NoUselessValueFunctionCallsRule

This rule will check if unnecessary calls to the 'value()' function are made

### Examples

Calling the following functions;

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

This rule will check if unnecessary calls to the 'with()' function are made

### Examples

Calling the following functions;

```php
$foo = with('foo');
$bar = with('bar', null);
```

will result in errors;

```
Calling the helper function 'with()' with only one argument simply returns the value itself. if you want to chain methods on a construct, use '(new ClassName())->foo()' instead
Calling the helper function 'with()' without a closure as the second argument simply returns the value without doing anything
```

## DeferrableServiceProviderMissingProvidesRule

This rule will check for a missing 'provides' method in deferrable ServiceProviders.

### Examples

A correct `DeferrableProvider` returns an array of `string`s or `class-string`s in the 'provides' method:

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

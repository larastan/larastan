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

## NoHelperDependingOnBootedApplicationUsageRule

Except for a small subset of functions, most of the global helpers in laravel are dependent in some way or another on the state of a booted application. And while these functions are convenient ways to shorten the amount of code you have to write, they couple your code with the Laravel codebase. And furthermore, they decrease the testability of your codebase. This rule prevents the usage of those specific coupled helper methods, while still allowing usage of the other functions that don't rely on a booted application. for more information, see this [blog post](https://prinsfrank.nl/2022/09/20/How-to-write-decoupled-unit-tests-in-Laravel).

This rule is disabled by default. You can enable it by adding
```
parameters:
    checkDecoupledCode: true
```
to your `phpstan.neon` file.

### Examples

The following code

```php
app_path();
base_path();
config_path();
database_path();
resource_path();
public_path();
lang_path();
storage_path();
resolve();
app();
abort_if();
abort_unless();
__();
trans();
trans_choice();
action();
asset();
secure_asset();
route();
url();
secure_url();
redirect();
to_route();
back();
config();
logger();
info();
rescue();
request();
old();
response();
mix();
auth();
cookie();
encrypt();
decrypt();
bcrypt();
session();
csrf_token();
csrf_field();
broadcast();
dispatch();
event();
policy();
view();
validator();
cache();
env();
abort();
```

Will result in the following errors:

```text
Usage of the global function "app_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "path".
Usage of the global function "base_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "basePath".
Usage of the global function "config_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "configPath".
Usage of the global function "database_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "databasePath".
Usage of the global function "resource_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "resourcePath".
Usage of the global function "public_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "publicPath".
Usage of the global function "lang_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "langPath".
Usage of the global function "storage_path" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "storagePath".
Usage of the global function "resolve" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "make".
Usage of the global function "app" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "make".
Usage of the global function "abort_if" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "abort" within an if statement.
Usage of the global function "abort_unless" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "abort" within an if statement.
Usage of the global function "__" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Translation\Translator and use method "translate".
Usage of the global function "trans" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Translation\Translator and use method "translate".
Usage of the global function "trans_choice" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Translation\Translator and use method "choice".
Usage of the global function "action" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "action".
Usage of the global function "asset" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "asset".
Usage of the global function "secure_asset" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "asset" with the second parameter set to "true".,
Usage of the global function "route" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "route".
Usage of the global function "url" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "url", "current" for the current url, "full" for the full url or "previous" for the previous url.
Usage of the global function "secure_url" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\UrlGenerator and use method "url" with the third parameter set to "true".
Usage of the global function "redirect" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Routing\Redirector and use method "to".
Usage of the global function "to_route" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Routing\Redirector and use method "route".
Usage of the global function "back" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Routing\Redirector and use method "back".
Usage of the global function "config" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Config\Repository and use method "all" or "get".
Usage of the global function "logger" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Log\LogManager and use method "debug" or the class itself when currently called without parameters.
Usage of the global function "info" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Log\LogManager and use method "info".
Usage of the global function "rescue" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Debug\ExceptionHandler and use method "report" within a try-catch.
Usage of the global function "request" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Http\Request.
Usage of the global function "old" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Http\Request and use method "old".
Usage of the global function "response" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Routing\ResponseFactory and use the class itself or method "make" when originally called with arguments.
Usage of the global function "mix" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Foundation\Mix and invoke the class: "$mix()".
Usage of the global function "auth" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Auth\Factory and use the class itself or method "guard" when originally called with arguments.
Usage of the global function "cookie" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Cookie\Factory and use the class itself or method "make" when originally called with arguments.
Usage of the global function "encrypt" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Encryption\Encrypter and use method "encrypt".
Usage of the global function "decrypt" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Encryption\Encrypter and use method "decrypt".
Usage of the global function "bcrypt" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Hashing\HashManager and use "->driver('bcrypt')->make()".
Usage of the global function "session" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Session\SessionManager and use the class itself or method "get" when originally called with arguments.
Usage of the global function "csrf_token" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Session\SessionManager and use method "token".
Usage of the global function "csrf_field" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Session\SessionManager and use "new HtmlString('<input type="hidden" name="_token" value="' . $sessionManager->token() . '">')".
Usage of the global function "broadcast" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Broadcasting\BroadcastManager and use method "event".
Usage of the global function "dispatch" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Bus\Dispatcher and use method "dispatch".
Usage of the global function "event" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Events\Dispatcher and use method "dispatch".
Usage of the global function "policy" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Auth\Access\Gate and use method "getPolicyFor".
Usage of the global function "view" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\View\Factory and use method "make".
Usage of the global function "validator" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Validation\Factory and use method "make".
Usage of the global function "cache" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Cache\CacheManager and use method "get".
Usage of the global function "env" is highly dependent on a booted application and makes this code tightly coupled. Instead, Set the environment key in a configuration file so configuration caching doesn't break your application, inject Illuminate\Config\Repository and use method "get".
Usage of the global function "abort" is highly dependent on a booted application and makes this code tightly coupled. Instead, Inject Illuminate\Contracts\Foundation\Application and use method "abort".
```

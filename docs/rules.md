# Rules

All rules that are specific to Laravel applications 
are listed here with their configurable options.


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

## NoInvalidRouteAction

Checks if the class and method that a route uses exist when registering a route using 
the Route facade.

#### Examples

```php
Route::get('/hello', [UserController::class, 'nonExistingMethod']);

Route::match(['put', 'patch'], '/endpoint', 'App\Http\Controllers\UserTypoController@index');

Route::post('/post', [
    'uses' => 'App\Http\Controllers\UserController@typo',
]);
```

results in the following errors:
```
Detected non-existing method 'nonExistingMethod' on class 'App\\Http\\Controllers\\UserController during route registration.
Detected non-existing class 'App\Http\Controllers\UserTypoController' during route registration.
Detected non-existing method 'typo' on class 'App\\Http\\Controllers\\UserController during route registration.
```

This rule assumes that no [namespace is set on your route groups](https://laravel.com/docs/7.x/routing#route-group-namespaces).
Since Laravel has a namespace set by default in the `RouteServiceProvider`, this rule is also disabled by default.

To enable this rule, set:
```
parameters:
    noInvalidRouteAction: true
```

in your `phpstan.neon` config file.

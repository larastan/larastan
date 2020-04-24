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
noUnnecessaryCollectionCall: false
```
to your `phpstan.neon` file.

You can also configure the collection methods which this rule 
checks for. By default, all collection methods are checked. 
To only enable a specific set of methods, you could set the
 `noUnnecessaryCollectionCallOnly` configuration key. For example:
```
noUnnecessaryCollectionCallOnly: ['count', 'first']
```
will only throw errors on the `count` and `first` methods.
The inverse is also configurable, to not throw an exception
on the `contains` method, one could set the following value:
```
noUnnecessaryCollectionCallExcept: ['contains']
```


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

## model-property
The `model-property` type is a generic type that validates whether a string 
exists as a property on a subclass of `\Illuminate\Database\Eloquent\Model`. 

Given the type `model-property<\App\Models\User>`, Larastan will check whether 
the string exists as a property on the `\App\Models\User` class. This is true if 
Larastan finds a column definition on the corresponding table in one of your migrations.

**Example:**

```php
class User extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @phpstan-param model-property<self> $column
     * @param string $column
     * @return Builder
     */
    public function scopeWhereTrue($query, string $column): Builder
    {
        return $query->where($column, true);
    }
}
```

Larastan will then make sure that the string passed to the `whereTrue` scope
actually exists as a property on your User model.

This type is disabled by default. To allow Larastan to inspect these strings, set
```
parameters:
    checkModelProperties: true
```
in your configuration file.
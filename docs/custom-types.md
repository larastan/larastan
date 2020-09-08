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

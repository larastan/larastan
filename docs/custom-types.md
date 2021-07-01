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
`model-property` extends the built-in `string` type and acts like a string in the type level. But during the analysis if Larastan finds that an argument of the method or a function has a `model-property<ModelName>`, it'll try to check that the given argument value is actually a property of the model.

All of the Laravel core methods have this type thanks to the stubs. So whenever you use a Eloquent builder, relation or a model method that expects a column, it'll be checked by Larastan if the column actually exists. But you can also typehint any argument with `model-property` in your code.

The actual check is done by the `ModelPropertyRule`. You can read the details [here](rules.md#ModelPropertyRule).


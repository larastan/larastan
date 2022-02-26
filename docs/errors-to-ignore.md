## Errors To Ignore

Some parts of Laravel are currently too magical for Larastan/PHPStan to understand.

If you hit those errors in your project, you can add them to your `phpstan.neon` as needed.
[Learn more about ignoring errors in PHPStan.](https://phpstan.org/user-guide/ignoring-errors)

### Higher Order Messages

Although Larastan has support for [HigherOrderCollectionProxy](https://laravel.com/docs/collections#higher-order-messages), you can still have some errors if you are using higher order messages with `Support\Collection` rather than `Eloquent\Collection` . 

```neon
- '#Call to an undefined method Illuminate\\Support\\HigherOrder#'
```

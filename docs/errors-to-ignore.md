## Errors To Ignore

Some parts of Laravel are currently too magical for Larastan/PHPStan to understand.

If you hit those errors in your project, you can add them to your `phpstan.neon` as needed.
For example:

```neon
ignoreErrors:
- '#Result of function abort \(void\) is used#'
```

> Tip: If you set `reportUnmatchedIgnoredErrors: true`, you will be notified once Larastan
> properly recognizes what's going on, and you no longer have to ignore the error.

### Higher Order Messages

This comes up when using [higher order messages](https://laravel.com/docs/collections#higher-order-messages). 

```neon
- '#Call to an undefined method Illuminate\\Support\\HigherOrder#'
```

### Factories

This comes up when you add `database/factories` to your analysed paths.

```neon
- path: database/factories/*
  message: '#Undefined variable: \$factory#'
```

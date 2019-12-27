# Upgrade Guide

## Upgrading To 0.5 From 0.4

### Updating Dependencies

Update your `nunomaduro/larastan` dependency to `^0.5` in your `composer.json` file.

### `artisan code:analyse`

The artisan `code:analyse` command is no longer available. Therefore, you need to:

1. Start using the phpstan command to launch Larastan.

```bash
./vendor/bin/phpstan analyse
```

If you are getting the error `Allowed memory size exhausted`, then you can use the `--memory-limit` option fix the problem:

```bash
./vendor/bin/phpstan analyse --memory-limit=2G
```

2. Create a `phpstan.neon` or `phpstan.neon.dist` file in the root of your application that might look like this:

```
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:

    paths:
        - app

    # The level 7 is the highest level
    level: 5

    ignoreErrors:
        - '#Unsafe usage of new static#'

    excludes_analyse:
        - ./*/*/FileToBeExcluded.php

    checkMissingIterableValueType: false
```

### Misc

You may want to be aware of all the BC breaks detailed in:

- PHPStan changelog: [github.com/phpstan/phpstan/releases/tag/0.12.0](https://github.com/phpstan/phpstan/releases/tag/0.12.0)

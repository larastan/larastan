**THIS PACKAGE IS STILL IN DEVELOPMENT**

<p align="center">
    <img src="https://raw.githubusercontent.com/nunomaduro/laravel-code-analyse/master/docs/example.png" alt="Laravel Code Analyse Example" height="300">
</p>

<p align="center">
  <a href="https://travis-ci.org/nunomaduro/laravel-code-analyse"><img src="https://img.shields.io/travis/nunomaduro/laravel-code-analyse/master.svg" alt="Build Status"></img></a>
  <a href="https://scrutinizer-ci.com/g/nunomaduro/laravel-code-analyse"><img src="https://img.shields.io/scrutinizer/g/nunomaduro/laravel-code-analyse.svg" alt="Quality Score"></img></a>
  <a href="https://packagist.org/packages/nunomaduro/laravel-code-analyse"><img src="https://poser.pugx.org/nunomaduro/laravel-code-analyse/d/total.svg" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/nunomaduro/laravel-code-analyse"><img src="https://poser.pugx.org/nunomaduro/laravel-code-analyse/v/stable.svg" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/nunomaduro/laravel-code-analyse"><img src="https://poser.pugx.org/nunomaduro/laravel-code-analyse/license.svg" alt="License"></a>
</p>

## About Laravel Code Analyse

Laravel Code Analyse was created by, and is maintained by [Nuno Maduro](https://github.com/nunomaduro), and is a [phpstan/phpstan](https://github.com/phpstan/phpstan) wrapper for Laravel.

## Installation & Usage

> **Requires [PHP 7.1.3+](https://php.net/releases/)**

You may use [Composer](https://getcomposer.org) to install Laravel Code Analyse as a development dependency into your Laravel project:
```bash
composer require --dev nunomaduro/laravel-code-analyse
```

Once you have installed Laravel Code Analyse, you may start analyzing your code using the `code:analyse` Artisan command.
```bash
php artisan code:analyse
```

## Rule levels

You can choose from currently 8 levels: (0 is the loosest and 7 is the strictest) by passing `--level` to `analyse` command. Default level is `1`. You can also use `--level max` as an alias for the highest level.

```bash
php artisan code:analyse --level=max
```

## Contributing

Thank you for considering to contribute to Laravel Code Analyse. All the contribution guidelines are mentioned [here](CONTRIBUTING.md).

You can have a look at the [CHANGELOG](CHANGELOG.md) for constant updates & detailed information about the changes. You can also follow the twitter account for latest announcements or just come say hi!: [@enunomaduro](https://twitter.com/enunomaduro)

## Support the development
**Do you like this project? Support it by donating**

- PayPal: [Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66BYDWAT92N6L)
- Patreon: [Donate](https://www.patreon.com/nunomaduro)

## Credits

- [weebly/phpstan-laravel](https://github.com/weebly/phpstan-laravel) - Some code was inspired on this package.

## License

Laravel Code Analyse is an open-sourced software licensed under the [MIT license](LICENSE.md).

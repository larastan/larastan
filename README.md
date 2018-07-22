<p align="center">
    <img src="https://raw.githubusercontent.com/nunomaduro/larastan/master/docs/logo.png" alt="Larastan Logo" width="300">
    <br><br>
    <img src="https://raw.githubusercontent.com/nunomaduro/larastan/master/docs/example.png" alt="Larastan Example" height="300">
</p>

<p align="center">
  <a href="https://travis-ci.org/nunomaduro/larastan"><img src="https://img.shields.io/travis/nunomaduro/larastan/master.svg" alt="Build Status"></img></a>
  <a href="https://scrutinizer-ci.com/g/nunomaduro/larastan"><img src="https://img.shields.io/scrutinizer/g/nunomaduro/larastan.svg" alt="Quality Score"></img></a>
  <a href="https://packagist.org/packages/nunomaduro/larastan"><img src="https://poser.pugx.org/nunomaduro/larastan/d/total.svg" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/nunomaduro/larastan"><img src="https://poser.pugx.org/nunomaduro/larastan/v/stable.svg" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/nunomaduro/larastan"><img src="https://poser.pugx.org/nunomaduro/larastan/license.svg" alt="License"></a>
</p>

------

## About Larastan

Larastan was created by, and is maintained by [Nuno Maduro](https://github.com/nunomaduro), and is a [phpstan/phpstan](https://github.com/phpstan/phpstan) wrapper for Laravel. Larastan focuses on **finding errors in your code** without actually running it. It catches whole classes of bugs even **before you write tests** for the code.

- Supports [Laravel](https://laravel.com)'s **beautiful magic**.
- This package is still in development. Please report false positives using GitHub [issues](https://github.com/nunomaduro/larastan/issues).

## Installation & Usage

> **Requires:**
- **[PHP 7.1.3+](https://php.net/releases/)**
- **[Laravel 5.6+](https://github.com/laravel/laravel)**

You may use [Composer](https://getcomposer.org) to install Larastan as a development dependency into your Laravel project:
```bash
composer require nunomaduro/larastan
```

Once you have installed Larastan, you may start analyzing your code using the `code:analyse` Artisan command.
```bash
php artisan code:analyse
```

### Rule levels

You can choose from currently 8 levels: (0 is the loosest and 7 is the strictest) by passing `--level` to `analyse` command. Default level is `5`. You can also use `--level max` as an alias for the highest level.

```bash
php artisan code:analyse --level=max
```

### Paths

You can choose which directories you want to analyze, by default, the analyzed directory will be the `app`.

```bash
php artisan code:analyse --paths="modules,app,domain"
```

## Contributing

Thank you for considering to contribute to Larastan. All the contribution guidelines are mentioned [here](CONTRIBUTING.md).

You can have a look at the [CHANGELOG](CHANGELOG.md) for constant updates & detailed information about the changes. You can also follow the Twitter account for latest announcements or just come say hi!: [@enunomaduro](https://twitter.com/enunomaduro).

## Support the development
**Do you like this project? Support it by donating**

- PayPal: [Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66BYDWAT92N6L)
- Patreon: [Donate](https://www.patreon.com/nunomaduro)

## Credits

- [weebly/phpstan-laravel](https://github.com/weebly/phpstan-laravel) - Some code was inspired on this package.
- [@Caneco](http://github.com/caneco) - For making the logo.

## License

Larastan is an open-sourced software licensed under the [MIT license](LICENSE.md).
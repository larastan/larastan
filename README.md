<p align="center">
    <img src="https://raw.githubusercontent.com/nunomaduro/larastan/master/docs/logo.png" alt="Larastan Logo" width="300">
    <br><br>
    <img src="https://raw.githubusercontent.com/nunomaduro/larastan/master/docs/example.png" alt="Larastan Example" height="300">
</p>

<p align="center">
  <a href="https://travis-ci.org/nunomaduro/larastan"><img src="https://img.shields.io/travis/nunomaduro/larastan/master.svg" alt="Build Status"></img></a>
  <a href="https://packagist.org/packages/nunomaduro/larastan"><img src="https://poser.pugx.org/nunomaduro/larastan/d/total.svg" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/nunomaduro/larastan"><img src="https://poser.pugx.org/nunomaduro/larastan/v/stable.svg" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/nunomaduro/larastan"><img src="https://poser.pugx.org/nunomaduro/larastan/license.svg" alt="License"></a>
</p>

------

## ⚗️ About Larastan

Larastan was created by [Nuno Maduro](https://github.com/nunomaduro), got artwork designed by [@Caneco](http://github.com/caneco), is maintained by [Can Vural](https://github.com/canvural) and [Viktor Szépe](https://github.com/szepeviktor), and is a [phpstan/phpstan](https://github.com/phpstan/phpstan) wrapper for Laravel. Larastan focuses on **finding errors in your code** without actually running it. It catches whole classes of bugs even **before you write tests** for the code.

- Adds static typing to Laravel to improve developer productivity and **code quality**
- Supports most of [Laravel](https://laravel.com)'s **beautiful magic**
- Discovers bugs in your code without running it

## ✨ Get started

> **Requires:**
- **[PHP 7.1.3+](https://php.net/releases/)**
- **[Laravel 6.0+](https://github.com/laravel/laravel)**

First, you may use [Composer](https://getcomposer.org) to install Larastan as a development dependency into your Laravel project:
```bash
composer require --dev nunomaduro/larastan
```

> Using Larastan for analysing Laravel packages? You may need to install `orchestra/testbench`.

Then, create a `phpstan.neon` or `phpstan.neon.dist` file in the root of your application. It might look like this:

```
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:

    # You can choose which directories you want to analyze, 
    # by default, the analyzed directory will be the app.
    paths:
        - app

    # You can choose from currently 8 levels: 0 is the 
    # loosest and 7 is the strictest. You can also
    # use "max" as alias for the highest level.
    level: 5

    # If some issue in your code base is not easy to fix or just simply 
    # want to deal with it later, you can exclude error messages 
    # from the analysis result with regular expressions:
    ignoreErrors:
        - '#Access to an undefined property App\\Demo\\[a-zA-Z0-9\\_]+::\$[a-zA-Z0-9\\_]+\.#'
        - '#Call to an undefined method App\\Http\\Resources\\DemoResource::DemoMethod\(\)\.#'

    # To exclude an error in a specific directory
    # or file, specify a path or paths along
    # with the message:
    excludes_analyse:
        - /*/*/FileToBeExcluded.php

    # For all available options, please take
    # a look at the PHPStan documentation:
    # https://github.com/phpstan/phpstan
    checkMissingIterableValueType: false
```

Finally, you may start analyzing your code using the `./vendor/bin/phpstan analyse` command.

## 👊🏻 Contributing

Thank you for considering contributing to Larastan. All the contribution guidelines are mentioned [here](CONTRIBUTING.md).

You can have a look at the [CHANGELOG](CHANGELOG.md) for constant updates & detailed information about the changes. You can also follow the Twitter account for the latest announcements or just come say hi!: [@enunomaduro](https://twitter.com/enunomaduro).

## ❤️ Support the development

**Do you like this project? Support it by donating**

- PayPal: [Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=66BYDWAT92N6L)
- Patreon: [Donate](https://www.patreon.com/nunomaduro)

## 📖 License

Larastan is an open-sourced software licensed under the [MIT license](LICENSE.md).

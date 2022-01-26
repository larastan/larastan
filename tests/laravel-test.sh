#!/bin/bash

set -e

echo "Install Laravel"
composer create-project --quiet --prefer-dist "laravel/laravel=dev-master" ../laravel
cd ../laravel/

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../larastan", "options": { "symlink": false }} ],|' -i composer.json
# Work-around for conflicting psr/log versions
composer require --dev --no-update "nunomaduro/larastan:dev-master"
composer update

echo "
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:

    paths:
        - app

    # The level 9 is the highest level
    level: 5

    ignoreErrors:
        - '#PHPDoc tag @var#'

    excludePaths:
        - ./*/*/FileToBeExcluded.php

    checkMissingIterableValueType: false
" > phpstan.neon

echo "Test Laravel"
vendor/bin/phpstan analyse app
cd -

echo "Test Laravel from other working directories"
../laravel/vendor/bin/phpstan analyse ../laravel/app -c ../laravel/phpstan.neon

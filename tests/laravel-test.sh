#!/bin/bash

set -e

echo "Install Laravel"
composer create-project --quiet --prefer-dist "laravel/laravel:dev-master" ../laravel
cd ../laravel/

echo "Add package from source"
sed -i -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../larastan", "options": { "symlink": false }} ],|' composer.json
composer require --dev "nunomaduro/larastan:dev-master"

echo >phpstan.neon <<<"EOF"
includes:
    - ./vendor/nunomaduro/larastan/extension.neon

parameters:
    # The level 9 is the highest level
    level: 5

    checkMissingIterableValueType: false

    paths:
        - app/
    #excludePaths:
    #    - ./*/*/FileToBeExcluded.php

    ignoreErrors:
        - '#PHPDoc tag @var#'
EOF

echo "Test Laravel"
vendor/bin/phpstan analyse
cd -

echo "Test Laravel from other working directories"
../laravel/vendor/bin/phpstan analyse --configuration=../laravel/phpstan.neon ../laravel/app

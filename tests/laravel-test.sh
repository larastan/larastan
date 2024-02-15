#!/bin/bash

set -e

LARAVEL_VERSION_CONSTRAINT="${1:-^10}"

echo "Install Laravel ${LARAVEL_VERSION_CONSTRAINT}"
rm -rf tests/laravel
composer create-project --quiet --prefer-dist "laravel/laravel:${LARAVEL_VERSION_CONSTRAINT}" tests/laravel
cd tests/laravel

echo "Add Larastan from source"
composer config minimum-stability dev
composer config repositories.0 '{ "type": "path", "url": "../", "options": { "symlink": false } }'
# No version information with "type":"path"
composer require --dev "larastan/larastan:*"
composer du --optimize

cat >phpstan.neon <<"EOF"
includes:
    - vendor/larastan/larastan/extension.neon
parameters:
    level: 5
    paths:
        - app
EOF

echo "Test Laravel"
vendor/bin/phpstan analyse
cd -

echo "Test Laravel from other working directories"
tests/laravel/vendor/bin/phpstan analyse --configuration=tests/laravel/phpstan.neon tests/laravel/app

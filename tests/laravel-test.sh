#!/bin/bash

set -e

LARAVEL_VERSION_CONSTRAINT="${1:-^9.0}"

echo "Install Laravel ${LARAVEL_VERSION_CONSTRAINT}"
composer create-project --quiet --prefer-dist "laravel/laravel:${LARAVEL_VERSION_CONSTRAINT}" ../laravel
cd ../laravel/

echo "Add Larastan from source"
composer config minimum-stability dev
composer config repositories.0 '{ "type": "path", "url": "../larastan", "options": { "symlink": false } }'

# For Laravel version ^11.0, do a composer update to install different versions of laravel/framework
if [ "$LARAVEL_VERSION_CONSTRAINT" = "^11.0" ]; then
    composer update --with='laravel/framework:>=11.0.0 <11.15.0' --no-interaction --no-progress
fi

# No version information with "type":"path"
composer require --dev "larastan/larastan:*"
composer du -o

cat >phpstan.neon <<"EOF"
includes:
    - ./vendor/larastan/larastan/extension.neon
parameters:
    level: 5
    paths:
        - app/
EOF

echo "Test Laravel"
vendor/bin/phpstan analyse
cd -

echo "Test Laravel from other working directories"
../laravel/vendor/bin/phpstan analyse --configuration=../laravel/phpstan.neon ../laravel/app

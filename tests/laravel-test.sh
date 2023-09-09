#!/bin/bash

set -e

LARAVEL_VERSION_CONSTRAINT="${1:-^9.0}"

echo "Install Laravel ${LARAVEL_VERSION_CONSTRAINT}"
composer create-project --quiet --prefer-dist "laravel/laravel:${LARAVEL_VERSION_CONSTRAINT}" ../laravel
cd ../laravel/

echo "Add Larastan from source"
composer config minimum-stability dev
composer config repositories.0 '{ "type": "path", "url": "../larastan", "options": { "symlink": false } }'
# No version information with "type":"path"
composer require --dev "nunomaduro/larastan:*"

cat >phpstan.neon <<"EOF"
includes:
    - ./vendor/nunomaduro/larastan/extension.neon
parameters:
    level: 5
    paths:
        - app/
    parameters:
        ignoreErrors:
            -
                message: "#^Parameter \\#1 \\$name of function route expects route\\-name\\-string, string given\\.$#"
                count: 1
                path: app/Http/Middleware/Authenticate.php
EOF

echo "Test Laravel"
vendor/bin/phpstan analyse
cd -

echo "Test Laravel from other working directories"
../laravel/vendor/bin/phpstan analyse --configuration=../laravel/phpstan.neon ../laravel/app

#!/bin/bash

set -e

echo "Install Laravel"
composer create-project --quiet --prefer-dist "laravel/laravel:^9" ../laravel
cd ../laravel/

echo "Add Larastan from source"
composer config repositories.0 '{ "type": "path", "url": "../larastan", "options": { "symlink": false } }'
# No version information with "type":"path"
composer require --dev "nunomaduro/larastan:*"

echo "Fix commented namespace property in RouteServiceProvider"
sed -i -e 's|^\(\s*\)// \(protected \$namespace =\).*$|\1\2 null;|' app/Providers/RouteServiceProvider.php

cat >phpstan.neon <<"EOF"
includes:
    - ./vendor/nunomaduro/larastan/extension.neon
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

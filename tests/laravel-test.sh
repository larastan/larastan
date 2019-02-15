#!/bin/bash

set -e

echo "Prevent shallow repository error"
git fetch --unshallow

echo "Install Laravel"
travis_retry composer create-project --quiet --prefer-dist "laravel/laravel" laravel
cd laravel/

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "vcs", "url": "../" } ],|' -i composer.json
travis_retry composer require --dev "nunomaduro/larastan:dev-master#${TRAVIS_COMMIT}"

echo "Fix https://github.com/laravel/framework/pull/23825"
sed -e 's|@return \\Illuminate\\Http\\Response$|@return \\Symfony\\Component\\HttpFoundation\\Response|' \
    -i app/Exceptions/Handler.php

echo "Test Laravel"
php artisan code:analyse --level=max
cd -

echo "Install Lumen"
travis_retry composer create-project --quiet --prefer-dist "laravel/lumen" lumen
cd lumen/

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "vcs", "url": "../" } ],|' -i composer.json
travis_retry composer require --dev "nunomaduro/larastan:dev-master#${TRAVIS_COMMIT}"

echo "Test Lumen"
php artisan code:analyse --level=max

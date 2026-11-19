#!/bin/bash

set -e

LARAVEL_VERSION_CONSTRAINT="${1:-^13.0}"

echo "Install Laravel ${LARAVEL_VERSION_CONSTRAINT}"
composer create-project --quiet --prefer-dist "laravel/laravel:${LARAVEL_VERSION_CONSTRAINT}" ../laravel
cd ../laravel/
SAMPLE_APP_DIR="$(pwd)"
composer show --direct

echo "Add Larastan from source"
composer config minimum-stability dev
if [ "$LARAVEL_VERSION_CONSTRAINT" = "dev-master" ]; then
    # The skeleton's other packages lag behind the Laravel development version
    # and block its installation; the analysis needs only the framework.
    php -r '$c = json_decode(file_get_contents("composer.json"), true); $c["require"] = array_intersect_key($c["require"], ["php" => true, "laravel/framework" => true]); unset($c["require-dev"]); file_put_contents("composer.json", json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");'
    composer require laravel/framework:dev-master --with-all-dependencies --no-interaction --no-progress
fi
composer config repositories.0 '{ "type": "path", "url": "../larastan", "options": { "symlink": false } }'

# No version information with "type":"path"
composer require --dev --optimize-autoloader "larastan/larastan:*"

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

echo "Test Laravel from another working directory"
cd /tmp/
${SAMPLE_APP_DIR}/vendor/bin/phpstan analyse --configuration=${SAMPLE_APP_DIR}/phpstan.neon

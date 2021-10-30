#!/bin/bash

set -e

echo "Install Laravel"
composer create-project --quiet --prefer-dist "laravel/laravel" ../laravel
cd ../laravel/

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../larastan", "options": { "symlink": false }} ],|' -i composer.json
# Work-around for conflicting psr/log versions
composer require --dev --no-update "nunomaduro/larastan:*"
composer update

echo "Fix https://github.com/laravel/framework/pull/23825"
sed -e 's|@return \\Illuminate\\Http\\Response$|@return \\Symfony\\Component\\HttpFoundation\\Response|' \
    -i app/Exceptions/Handler.php

echo "Fix https://github.com/nunomaduro/larastan/pull/378#issuecomment-565706907"
sed -e 's/string/string|void/' -i app/Http/Middleware/Authenticate.php
sed '0,/}/s/}/}\nreturn;/' -i app/Http/Middleware/Authenticate.php

echo "Test Laravel"
vendor/bin/phpstan analyse app --level=5 -c vendor/nunomaduro/larastan/extension.neon
cd -

echo "Test Laravel from other working directories"
../laravel/vendor/bin/phpstan analyse ../laravel/app --level=5 -c ../laravel/vendor/nunomaduro/larastan/extension.neon

echo "Install Lumen"
composer create-project --quiet --prefer-dist "laravel/lumen" ../lumen
cd ../lumen/

if [ -f app/Models/User.php ]; then
    echo "Fix types in User.php"
    sed -i -e 's#@var array#@var string[]#' app/Models/User.php
fi

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../larastan", "options": { "symlink": false }} ],|' -i composer.json
composer require --dev "nunomaduro/larastan:*"

echo "Fix Handler::render return type"
sed -e 's/@return \\Illuminate\\Http\\Response|\\Illuminate\\Http\\JsonResponse$/@return \\Symfony\\Component\\HttpFoundation\\Response/' \
    -i app/Exceptions/Handler.php

echo "Add Larastan to Lumen"
cat <<"EOF" | patch -p 0
--- bootstrap/app.php     2019-02-15 12:31:48.469773495 +0000
+++ bootstrap/app.php     2019-02-15 12:27:43.358369317 +0000
@@ -23,6 +23,9 @@
     dirname(__DIR__)
 );

+$app->instance('path.storage', app()->basePath() . DIRECTORY_SEPARATOR . 'storage');
+$app->configure('view');
+
 // $app->withFacades();

 // $app->withEloquent();
@@ -78,6 +80,7 @@
 |
 */

+
 // $app->register(App\Providers\AppServiceProvider::class);
 // $app->register(App\Providers\AuthServiceProvider::class);
 // $app->register(App\Providers\EventServiceProvider::class);
EOF

echo "Test Lumen"
vendor/bin/phpstan analyse app --level=5 -c vendor/nunomaduro/larastan/extension.neon
cd -

echo "Test Lumen from other working directories"
../lumen/vendor/bin/phpstan analyse ../lumen/app --level=5 -c ../lumen/vendor/nunomaduro/larastan/extension.neon

#!/bin/bash

set -e

echo "Install Laravel"
travis_retry composer create-project --quiet --prefer-dist "laravel/laravel" ../laravel
cd ../laravel/

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../larastan" } ],|' -i composer.json
travis_retry composer require --dev "nunomaduro/larastan:*"

echo "Fix https://github.com/laravel/framework/pull/23825"
sed -e 's|@return \\Illuminate\\Http\\Response$|@return \\Symfony\\Component\\HttpFoundation\\Response|' \
    -i app/Exceptions/Handler.php

echo "Fix https://github.com/nunomaduro/larastan/pull/378#issuecomment-565706907"
sed -e 's/string/string|void/' -i app/Http/Middleware/Authenticate.php
sed '0,/}/s/}/}\nreturn;/' -i app/Http/Middleware/Authenticate.php

echo "Test Laravel"
vendor/bin/phpstan analyse app --level=5 -c vendor/nunomaduro/larastan/extension.neon
cd -

echo "Install Lumen"
travis_retry composer create-project --quiet --prefer-dist "laravel/lumen" ../lumen
cd ../lumen/

echo "Add package from source"
sed -e 's|"type": "project",|&\n"repositories": [ { "type": "path", "url": "../larastan" } ],|' -i composer.json
travis_retry composer require --dev "nunomaduro/larastan:*"

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

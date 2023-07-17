<?php

use Illuminate\Filesystem\Filesystem;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/tests/Integration/data/blade-view.php';
require_once __DIR__.'/tests/Integration/data/test-case-extension.php';
require_once __DIR__.'/tests/Type/data/custom-eloquent-builder.php';
require_once __DIR__.'/tests/Type/data/eloquent-builder.php';
require_once __DIR__.'/tests/Type/data/model-scopes.php';

$filesystem = new Filesystem();

$filesystem->copyDirectory(__DIR__.'/tests/application/database/migrations', __DIR__.'/vendor/orchestra/testbench-core/laravel/database/migrations');
$filesystem->copyDirectory(__DIR__.'/tests/application/database/schema', __DIR__.'/vendor/orchestra/testbench-core/laravel/database/schema');
$filesystem->copyDirectory(__DIR__.'/tests/application/config', __DIR__.'/vendor/orchestra/testbench-core/laravel/config');
$filesystem->copyDirectory(__DIR__.'/tests/application/resources', __DIR__.'/vendor/orchestra/testbench-core/laravel/resources');
$filesystem->copyDirectory(__DIR__.'/tests/application/app/Console', __DIR__.'/vendor/orchestra/testbench-core/laravel/app/Console');

<?php

require_once __DIR__.'/vendor/autoload.php';

$filesystem = new \Illuminate\Filesystem\Filesystem();

$filesystem->copyDirectory(__DIR__.'/tests/application/database/migrations', __DIR__.'/vendor/orchestra/testbench-core/laravel/database/migrations');
$filesystem->copyDirectory(__DIR__.'/tests/application/database/schema', __DIR__.'/vendor/orchestra/testbench-core/laravel/database/schema');
$filesystem->copyDirectory(__DIR__.'/tests/application/config', __DIR__.'/vendor/orchestra/testbench-core/laravel/config');
$filesystem->copyDirectory(__DIR__.'/tests/application/resources', __DIR__.'/vendor/orchestra/testbench-core/laravel/resources');

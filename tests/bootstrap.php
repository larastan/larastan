<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Support\Facades\File;

@File::makeDirectory(dirname(__DIR__).'/vendor/nunomaduro/larastan', 0755, true);
@File::copy(dirname(__DIR__).'/bootstrap.php', dirname(__DIR__).'/vendor/nunomaduro/larastan/bootstrap.php');
File::copyDirectory(__DIR__.'/Application/database/migrations', __DIR__.'/../vendor/orchestra/testbench-core/laravel/database/migrations');
File::copyDirectory(__DIR__.'/Application/database/schema', __DIR__.'/../vendor/orchestra/testbench-core/laravel/database/schema');
File::copyDirectory(__DIR__.'/Application/config', __DIR__.'/../vendor/orchestra/testbench-core/laravel/config');
File::copyDirectory(__DIR__.'/Application/resources', __DIR__.'/../vendor/orchestra/testbench-core/laravel/resources');

Carbon::macro('foo', static function (): string {
    return 'foo';
});

\Illuminate\Database\Eloquent\Builder::macro('globalCustomMacro', function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

\Illuminate\Support\Facades\Route::macro('facadeMacro', function (): int {
    return 5;
});

\Illuminate\Auth\SessionGuard::macro('sessionGuardMacro', function (): int {
    return 5;
});

\Illuminate\Auth\RequestGuard::macro('requestGuardMacro', function (): int {
    return 5;
});

class CustomCollectionMacro
{
    public function registerMacro()
    {
        \Illuminate\Support\Collection::macro('customCollectionMacro', [$this, 'customMacro']);
    }

    public function customMacro(): string
    {
        return 'customMacro';
    }
}

(new CustomCollectionMacro)->registerMacro();

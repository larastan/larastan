<?php

declare(strict_types=1);

use Carbon\Carbon;

Carbon::macro('customCarbonMacro', static function (): string {
    return 'foo';
});

class CarbonMixin
{
    public static function customCarbonMixinStatic(): \Closure
    {
        return static function (): int {
            return 69;
        };
    }

    public function customCarbonMixinInstance(): \Closure
    {
        return function (): bool {
            return true;
        };
    }
}
Carbon::mixin(new CarbonMixin);

\Illuminate\Database\Eloquent\Builder::macro('globalCustomMacro', function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

\Illuminate\Database\Query\Builder::macro('globalCustomDatabaseQueryMacro', function (string $arg = 'foobar', int $b = 5): string {
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

\Illuminate\Support\Str::macro('trimMacro', 'trim');
\Illuminate\Support\Str::macro('asciiAliasMacro', \Illuminate\Support\Str::class.'::ascii');

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

if (version_compare(PHP_VERSION, '8.1.0', '>=') && version_compare(PHP_VERSION, '8.2.0', '<')) {
    include_once 'enum-definition.php';
}

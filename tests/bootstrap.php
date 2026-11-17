<?php

declare(strict_types=1);

use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Builder::macro('globalCustomMacro', static function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

Builder::macro('getColumns', static function (): string {
    return 'macro';
});

Builder::macro('latestFirst', function (string $column): Builder {
    return $this->orderBy($column, 'desc');
});

Relation::macro('latestFirst', function (string $column): Relation {
    return $this->orderBy($column, 'desc');
});

\Illuminate\Database\Query\Builder::macro('globalCustomDatabaseQueryMacro', static function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

Route::macro('facadeMacro', static function (): int {
    return 5;
});

SessionGuard::macro('sessionGuardMacro', static function (): int {
    return 5;
});

RequestGuard::macro('requestGuardMacro', static function (): int {
    return 5;
});

Str::macro('trimMacro', 'trim');
Str::macro('asciiAliasMacro', Str::class . '::ascii');


Cache::macro('rememberIf', static fn ($cond, $key, $ttl, $callback): mixed => $cond ? Cache::remember($key, $ttl, $callback) : $callback());

class CustomCollectionMacro
{
    public function registerMacro(): void
    {
        Collection::macro('customCollectionMacro', [$this, 'customMacro']);
        Collection::macro('customCollectionMacroString', [self::class, 'customMacroString']);
    }

    public function customMacro(): string
    {
        return 'customMacro';
    }

    public function customMacroString(): string
    {
        return 'customMacroString';
    }
}

(new CustomCollectionMacro())->registerMacro();

if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    include_once 'enum-definition.php';
}

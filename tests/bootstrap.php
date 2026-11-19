<?php

declare(strict_types=1);

use Illuminate\Auth\RequestGuard;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory as EloquentFactory;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Mailable;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Fluent;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
use Illuminate\Testing\TestComponent;
use Illuminate\Testing\TestResponse;
use Illuminate\View\View;

Builder::macro('globalCustomMacro', static function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

Builder::macro('macroOnRelation', function (): string {
    return 'aa';
});

Relation::macro('macroOnRelation', function (): int {
    return 5;
});

// https://github.com/larastan/larastan/issues/2657
Builder::macro('latestFirst', function (string $column): Builder {
    return $this->orderBy($column, 'desc');
});

Relation::macro('latestFirst', function (string $column): Relation {
    return $this->orderBy($column, 'desc');
});

Builder::macro('getColumns', static function (): string {
    return 'macro';
});

\Illuminate\Database\Query\Builder::macro('globalCustomDatabaseQueryMacro', static function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

Route::macro('facadeMacro', function (): string|null {
    return $this->currentRouteName();
});

SessionGuard::macro('sessionGuardMacro', function () {
    return $this->getName();
});

RequestGuard::macro('requestGuardMacro', function (): bool {
    return $this->check();
});

Str::macro('trimMacro', 'trim');
Str::macro('probeLen', 'strlen');
Str::macro('probeCount', 'str_word_count');
Str::macro('asciiAliasMacro', Str::class . '::ascii');


Cache::macro('rememberIf', static fn ($cond, $key, $ttl, $callback): mixed => $cond ? Cache::remember($key, $ttl, $callback) : $callback());

Collection::macro('probeUntyped', function () {
    return 'literal';
});
Collection::macro('probeNativeNarrowed', function (): Collection {
    return collect([1, 2, 3])->map(fn (int $i) => (string) $i);
});
Collection::macro('probeThisGeneric', function () {
    return $this->take(2);
});
Collection::macro('probeThisFirst', fn () => $this->first());
Collection::macro('probeThisKeys', fn () => $this->keys());
Collection::macro('probeStatic', fn () => new static());
Collection::macro('probeParam', fn (int $n, $x) => [$n, $x]);
Collection::macro('probeRecursive', fn (int $n) => $n > 0 ? $this->probeRecursive($n - 1) : 'done');
Collection::macro('probeCallsOther', fn () => $this->probeThisGeneric()->probeThisFirst());
$probeCaptured = ['a' => 1];
Collection::macro('probeUse', function () use ($probeCaptured) {
    return $probeCaptured;
});
Collection::macro('probeSameLineA', fn () => 1); Collection::macro('probeSameLineB', fn () => 'b');
Collection::macro('probeFirstClass', strlen(...));
Builder::macro('probeBuilderThis', function () {
    return $this->where('id', 1);
});
Builder::macro('probeBuilderFirst', fn () => $this->first());
Str::macro('probeStr', fn (string $s) => Str::of($s)->upper());

Collection::macro('probeTypedThis', function (): Collection {
    return $this->take(2);
});
Collection::macro('probeTypedArray', function (): array {
    return $this->all();
});
Collection::macro('probeTypedMixed', fn (): mixed => $this->first());
Builder::macro('probeTypedBuilder', function (): Builder {
    return $this->where('id', 1);
});
Builder::macro('probeTypedModel', fn (): ?\Illuminate\Database\Eloquent\Model => $this->first());

// Classes that use Macroable but define their own __call.
CacheRepository::macro('cacheRepositoryMacro', function () {
    return $this->getStore();
});

Schedule::macro('scheduleMacro', function (): array {
    return $this->events();
});

DatabaseManager::macro('databaseManagerMacro', function (): string {
    return $this->getDefaultConnection();
});

EloquentFactory::macro('eloquentFactoryMacro', function () {
    return $this->count(3);
});

Relation::macro('relationMacro', function () {
    return $this->getRelated();
});

QueryBuilder::macro('queryBuilderMacro', function () {
    return $this->where('active', true);
});

FilesystemAdapter::macro('filesystemAdapterMacro', function (): array {
    return $this->files('exports');
});

HttpClientFactory::macro('httpClientFactoryMacro', function () {
    return $this->withHeaders(['Accept' => 'application/json']);
});

HttpClientResponse::macro('httpClientResponseMacro', function () {
    return $this->collect('data');
});

RedirectResponse::macro('redirectResponseMacro', function () {
    return $this->with('status', 'saved');
});

JsonResource::macro('jsonResourceMacro', function () {
    return $this->additional(['meta' => ['version' => 1]]);
});

Mailable::macro('mailableMacro', function () {
    return $this->subject('Welcome');
});

ProcessFactory::macro('processFactoryMacro', function () {
    return $this->newPendingProcess()->timeout(30);
});

RedisConnection::macro('redisConnectionMacro', function (): string|null {
    return $this->getName();
});

Router::macro('routerMacro', function () {
    return $this->getRoutes();
});

Fluent::macro('fluentMacro', function (): array {
    return $this->toArray();
});

Optional::macro('optionalMacro', function (): bool {
    return $this->offsetExists('id');
});

TestComponent::macro('testComponentMacro', function () {
    return $this->assertSee('Welcome');
});

TestResponse::macro('testResponseMacro', function () {
    return $this->assertStatus(422);
});

View::macro('viewMacro', function () {
    return $this->with('title', 'Dashboard');
});

RouteRegistrar::macro('routeRegistrarMacro', function () {
    return $this->prefix('admin');
});

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

include_once 'enum-definition.php';

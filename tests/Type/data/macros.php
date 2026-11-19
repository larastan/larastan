<?php

namespace Macros;

use App\Post;
use App\PostBuilder;
use App\User;
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
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Mailable;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Redis\Connections\Connection as RedisConnection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Fluent;
use Illuminate\Support\Optional;
use Illuminate\Support\Str;
use Illuminate\Testing\TestComponent;
use Illuminate\Testing\TestResponse;
use Illuminate\View\View;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Validation\ValidationException;
use PHPStan\TrinaryLogic;

use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

function test(): void
{
    try {
        Request::validate([]);
    } catch (ValidationException $e) {
        $foo = 'foo';
    }

    assertType('string', Builder::globalCustomMacro(b: 99));
    assertType('string', Post::globalCustomMacro(b: 99));
    assertType('string', PostBuilder::globalCustomMacro(b: 99));
    assertType('string', User::first()->accounts()->globalCustomMacro(b: 99));

    assertType('string', \Illuminate\Database\Query\Builder::globalCustomDatabaseQueryMacro(b: 99));
    assertType('string', Post::globalCustomDatabaseQueryMacro(b: 99));
    assertType('string', PostBuilder::globalCustomDatabaseQueryMacro(b: 99));
    assertType('string', User::first()->accounts()->globalCustomDatabaseQueryMacro(b: 99));

    assertType('string|null', Route::facadeMacro());
    assertType('string', Auth::sessionGuardMacro());
    assertType('bool', Auth::requestGuardMacro());

    assertType('string', collect([])->customCollectionMacro());
    assertType('string', Collection::customCollectionMacro());

    assertType('string', collect([])->customCollectionMacroString());
    assertType('string', Collection::customCollectionMacroString());

    assertType('string', Str::trimMacro(''));
    assertType('string', Str::asciiAliasMacro(''));

    assertType('mixed', Cache::rememberIf(true, 'key', 60, fn () => 'value'));

    assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);

    Request::macro('foo', function () {
        assertType(Request::class, $this);
    });

    Foo::macro('foo', function () {
        assertType(Foo::class, $this);
    });

    assertType('5', User::accounts()->macroOnRelation());
    assertType("'aa'", User::query()->macroOnRelation());
}

/** Classes that use Macroable but define their own __call. */
function testClassesOverridingMacroableCall(
    CacheRepository $cacheRepository,
    Schedule $schedule,
    DatabaseManager $databaseManager,
    EloquentFactory $eloquentFactory,
    Relation $relation,
    QueryBuilder $queryBuilder,
    FilesystemAdapter $filesystemAdapter,
    HttpClientFactory $httpClientFactory,
    HttpClientResponse $httpClientResponse,
    RedirectResponse $redirectResponse,
    JsonResource $jsonResource,
    Mailable $mailable,
    ProcessFactory $processFactory,
    RedisConnection $redisConnection,
    Router $router,
    Fluent $fluent,
    Optional $optional,
    TestComponent $testComponent,
    TestResponse $testResponse,
    View $view,
): void {
    assertType('Illuminate\Contracts\Cache\Store', $cacheRepository->cacheRepositoryMacro());
    assertType('array<Illuminate\Console\Scheduling\Event>', $schedule->scheduleMacro());
    assertType('string', $databaseManager->databaseManagerMacro());
    assertType('Illuminate\Database\Eloquent\Factories\Factory', $eloquentFactory->eloquentFactoryMacro());
    assertType('Illuminate\Database\Eloquent\Model', $relation->relationMacro());
    assertType('Illuminate\Database\Query\Builder', $queryBuilder->queryBuilderMacro());
    assertType('array<string>', $filesystemAdapter->filesystemAdapterMacro());
    assertType('Illuminate\Http\Client\PendingRequest', $httpClientFactory->httpClientFactoryMacro());
    assertType('Illuminate\Support\Collection', $httpClientResponse->httpClientResponseMacro());
    assertType('Illuminate\Http\RedirectResponse', $redirectResponse->redirectResponseMacro());
    assertType('Illuminate\Http\Resources\Json\JsonResource', $jsonResource->jsonResourceMacro());
    assertType('Illuminate\Mail\Mailable', $mailable->mailableMacro());
    assertType('Illuminate\Process\PendingProcess', $processFactory->processFactoryMacro());
    assertType('string|null', $redisConnection->redisConnectionMacro());
    assertType('Illuminate\Routing\RouteCollectionInterface', $router->routerMacro());
    assertType('array<mixed>', $fluent->fluentMacro());
    assertType('bool', $optional->optionalMacro());
    assertType('Illuminate\Testing\TestComponent', $testComponent->testComponentMacro());
    assertType('Illuminate\Testing\TestResponse', $testResponse->testResponseMacro());
    assertType('Illuminate\View\View', $view->viewMacro());

    assertType('Database\Factories\UserFactory', User::factory()->eloquentFactoryMacro());
    assertType('App\Account', (new User())->accounts()->relationMacro());
}

class Foo
{
    use Macroable;
}

function enumDefaultValue(): void
{
    assertType('string', Builder::macroWithEnumDefaultValue());
}

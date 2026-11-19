<?php

namespace MacroableForwardsCalls;

use App\UserResource;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Cache\TaggedCache;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Factory as HttpClientFactory;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Process\Factory as ProcessFactory;
use Illuminate\Testing\TestResponse;

use function PHPStan\Testing\assertType;

function test(
    CacheRepository $cacheRepository,
    TaggedCache $taggedCache,
    DatabaseManager $databaseManager,
    FilesystemAdapter $filesystemAdapter,
    HttpClientFactory $httpClientFactory,
    HttpClientResponse $httpClientResponse,
    ProcessFactory $processFactory,
    TestResponse $testResponse,
    UserResource $userResource,
): void {
    assertType('string', $databaseManager->getDriverName());
    assertType('int', $filesystemAdapter->fileSize('file.txt'));
    assertType('Illuminate\Http\Client\PendingRequest', $httpClientFactory->withHeaders([]));
    assertType('string', $httpClientResponse->getProtocolVersion());
    assertType('Illuminate\Process\PendingProcess', $processFactory->path('/tmp'));
    assertType('bool', $testResponse->isOk());

    assertType('Illuminate\Contracts\Cache\Store', $taggedCache->cacheRepositoryMacro());

    assertType('App\UserResource', $userResource->jsonResourceMacro());
    assertType('string', $userResource->getTable());

    assertType('Illuminate\Contracts\Cache\Store', $cacheRepository->cacheRepositoryMacro());
    assertType('*ERROR*', $processFactory->doesNotExist());
}

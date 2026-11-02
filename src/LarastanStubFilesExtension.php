<?php

declare(strict_types=1);

namespace Larastan\Larastan;

use Larastan\Larastan\Concerns\HasContainer;
use PHPStan\PhpDoc\StubFilesExtension;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Throwable;

use function array_keys;
use function array_values;
use function in_array;
use function iterator_to_array;
use function version_compare;

final class LarastanStubFilesExtension implements StubFilesExtension
{
    use HasContainer;

    private const REDIS_STUB_PATHS = [
        'Redis/Facade.stub',
        'Redis/PhpRedisFacade.stub',
        'Redis/PredisFacade.stub',
        'Redis/Connection.stub',
        'Redis/PhpRedisConnection.stub',
        'Redis/PredisConnection.stub',
    ];

    /** @param 'phpredis'|'predis'|null $redisDriver */
    public function __construct(private string|null $redisDriver)
    {
    }

    /** @inheritDoc */
    public function getFiles(): array
    {
        $stubDirectories = Finder::create()->directories()->name('/^\d+/')->in(__DIR__ . '/../stubs')->depth(0);

        // Include only applicable versions
        $stubDirectories
            ->filter(static fn (SplFileInfo $directory) => version_compare($directory->getFilename(), LARAVEL_VERSION, '<='))
            ->sort(static fn (SplFileInfo $a, SplFileInfo $b) => version_compare($a->getFilename(), $b->getFilename()));

        $files = [];

        $stubDirs = [__DIR__ . '/../stubs/common', ...array_keys(iterator_to_array($stubDirectories))];

        $stubFiles = Finder::create()->files()->name('*.stub')->in($stubDirs);

        $allowedRedisStubs = $this->redisStubPathsForDriver($this->resolveRedisDriver());

        foreach ($stubFiles as $stubFile) {
            $relative = $stubFile->getRelativePathname();

            if (in_array($relative, self::REDIS_STUB_PATHS, true) && ! in_array($relative, $allowedRedisStubs, true)) {
                continue;
            }

            $files[$relative] = $stubFile->getRealPath();
        }

        return array_values($files);
    }

    /** @return 'phpredis'|'predis'|'generic' */
    private function resolveRedisDriver(): string
    {
        if ($this->redisDriver === 'phpredis' || $this->redisDriver === 'predis') {
            return $this->redisDriver;
        }

        try {
            $config = $this->getContainer()->get('config');
        } catch (Throwable) {
            return 'generic';
        }

        if ($config === null) {
            return 'generic';
        }

        $client = $config->get('database.redis.client');

        if ($client === 'phpredis' || $client === 'predis') {
            return $client;
        }

        return 'generic';
    }

    /**
     * @param 'phpredis'|'predis'|'generic' $driver
     *
     * @return list<string>
     */
    private function redisStubPathsForDriver(string $driver): array
    {
        return match ($driver) {
            'phpredis' => [
                'Redis/PhpRedisFacade.stub',
                'Redis/PhpRedisConnection.stub',
            ],
            'predis' => [
                'Redis/PredisFacade.stub',
                'Redis/PredisConnection.stub',
            ],
            'generic' => [
                'Redis/Facade.stub',
                'Redis/Connection.stub',
            ],
        };
    }
}

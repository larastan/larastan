<?php

declare(strict_types=1);

namespace Type;

use Larastan\Larastan\Support\ConfigParser;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\File\FileHelper;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;
use function Orchestra\Testbench\laravel_version_compare;
use function sort;
use function sprintf;

class ConfigDynamicReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/config-helper-function.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/config-repository-method.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/config-reproduction.php');

        if (! laravel_version_compare('12.20.0', '>=')) {
            return;
        }

        yield from self::gatherAssertTypes(__DIR__ . '/data/config-facade-collection-method.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/config-repository-method-l12-20.php');
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(
        string $assertType,
        string $file,
        mixed ...$args,
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public function testConfigFilesAreFileDependencies(): void
    {
        $collector = self::getContainer()->getByType(ValueDependencyCollector::class);
        $file      = __DIR__ . '/data/config-file-dependency.php';

        // The second run hits the parsed config cache and must still declare the files.
        foreach ([1, 2] as $run) {
            $collector->startFile($file);
            self::processFile($file, static function (): void {
            });
            $dependencies = $collector->finishFile();

            $dependencyFiles = [];

            foreach ($dependencies['values'] as [$extensionClass, $key]) {
                $this->assertSame(FileResultCacheValueExtension::class, $extensionClass);
                $dependencyFiles[] = $key;
            }

            $fileHelper = self::getContainer()->getByType(FileHelper::class);
            $configPath = self::getContainer()->getByType(ConfigParser::class)->getConfigPaths()[0];
            $expected   = array_map(
                static fn (string $name): string => $fileHelper->normalizePath($configPath . '/' . $name . '.php'),
                ['auth', 'auth/defaults', 'test', 'test/foo', 'missing', 'missing/foo'],
            );

            sort($expected);
            sort($dependencyFiles);

            $this->assertSame($expected, $dependencyFiles, sprintf('Run %d', $run));
        }
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/config-with-config-paths.neon'];
    }
}

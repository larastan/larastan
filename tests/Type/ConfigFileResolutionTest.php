<?php

declare(strict_types=1);

namespace Type;

use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\File\FileHelper;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_column;

class ConfigFileResolutionTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/config-file-resolution.php');
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(
        string $assertType,
        string $file,
        mixed ...$args,
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public function testEveryFileThatCanHoldAKeyIsAFileDependency(): void
    {
        $collector = self::getContainer()->getByType(ValueDependencyCollector::class);
        $file      = __DIR__ . '/data/config-file-resolution.php';

        $collector->startFile($file);
        self::processFile($file, static function (): void {
        });
        $dependencyFiles = array_column($collector->finishFile()['values'], 1);

        $fileHelper = self::getContainer()->getByType(FileHelper::class);

        // config('nested.rcnest.v') can come from any prefix of the key in any config directory
        foreach (['config', 'modules/a/config', 'modules/b/config', 'literal[1]/config'] as $configDirectory) {
            foreach (['nested.php', 'nested/rcnest.php', 'nested/rcnest/v.php'] as $configFile) {
                $this->assertContains(
                    $fileHelper->normalizePath(__DIR__ . '/data/config-file-resolution/' . $configDirectory . '/' . $configFile),
                    $dependencyFiles,
                );
            }
        }

        foreach ($dependencyFiles as $dependencyFile) {
            $this->assertStringNotContainsString('*', $dependencyFile);
        }
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/config-file-resolution.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function Orchestra\Testbench\laravel_version_compare;

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

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/config-with-config-paths.neon'];
    }
}

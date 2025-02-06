<?php

declare(strict_types=1);

namespace Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

use function Orchestra\Testbench\laravel_version_compare;

class CollectionDynamicReturnTypeExtensionsTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-helper.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-make-static.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-stubs.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-generic-static-methods.php');

        if (laravel_version_compare('11.0.0', '>=') && laravel_version_compare('12.0.0', '<')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/collection-generic-static-methods-l11.php');
        }

        if (laravel_version_compare('12.0.0', '<')) {
            return;
        }

        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-generic-static-methods-l12.php');
    }

    /** @dataProvider dataFileAsserts */
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
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

use const PHP_VERSION_ID;

class CollectionDynamicReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-filter.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-where-not-null.php');

        if (PHP_VERSION_ID < 70400) {
            return;
        }

        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-filter-arrow-function.php');
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
        return [__DIR__ . '/../../extension.neon'];
    }
}

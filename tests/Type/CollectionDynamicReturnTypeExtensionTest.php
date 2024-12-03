<?php

declare(strict_types=1);

namespace Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

class CollectionDynamicReturnTypeExtensionTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-filter.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-reject.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-where-not-null.php');
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

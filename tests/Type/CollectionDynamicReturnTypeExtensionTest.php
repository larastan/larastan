<?php

declare(strict_types=1);

namespace Tests\Type;

class CollectionDynamicReturnTypeExtensionTest extends \PHPStan\Testing\TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__.'/data/collection-filter.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/collection-where-not-null.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/collection-filter-arrow-function.php');
    }

    /**
     * @dataProvider dataFileAsserts
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
        ...$args
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../../extension.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Type;

class CollectionDynamicReturnTypeExtensionTest extends \PHPStan\Testing\TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__.'/data/collection-filter.php');

        if (PHP_VERSION_ID >= 70400) {
            yield from $this->gatherAssertTypes(__DIR__.'/data/collection-filter-arrow-function.php');
        }
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

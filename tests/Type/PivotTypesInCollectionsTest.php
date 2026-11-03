<?php

declare(strict_types=1);

namespace Tests\Type;

use Generator;
use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PivotTypesInCollectionsTest extends TypeInferenceTestCase
{
    public static function dataFileAsserts(): Generator
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/pivot-types-in-collections.php');
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
        return [__DIR__ . '/data/config-pivot-types-in-collections.neon'];
    }
}

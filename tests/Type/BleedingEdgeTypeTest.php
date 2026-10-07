<?php

declare(strict_types=1);

namespace Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;

use function dirname;

class BleedingEdgeTypeTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/bleeding-edge/collection-keys.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bleeding-edge/collection-usage-inference.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bleeding-edge/closure-usage-inference.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bleeding-edge/eloquent-collection-map.php');
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
        // PHPStan's own bleedingEdge.neon includes the config through a phar alias that only exists when the phar is executed.
        $pharRoot = dirname((string) (new ReflectionClass(TypeInferenceTestCase::class))->getFileName(), 3);

        return [
            __DIR__ . '/../phpstan-tests.neon',
            $pharRoot . '/conf/bleedingEdge.neon',
        ];
    }
}

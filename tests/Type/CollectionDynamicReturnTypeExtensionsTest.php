<?php

declare(strict_types=1);

namespace Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

use function version_compare;

class CollectionDynamicReturnTypeExtensionsTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-helper.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-make-static.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-stubs.php');

        if (version_compare(LARAVEL_VERSION, '9.48.0', '<')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/collection-generic-static-methods.php');
        } else {
            yield from self::gatherAssertTypes(__DIR__ . '/data/collection-generic-static-methods-l948.php');
        }
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

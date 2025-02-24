<?php

declare(strict_types=1);

namespace Type;

use PHPStan\Testing\TypeInferenceTestCase;

use function version_compare;

use const PHP_VERSION;

class MethodsClassReflectionExtensionTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/macros.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/redirect-response.php');

        if (! version_compare(PHP_VERSION, '8.1.0', '>=')) {
            return;
        }

        yield from self::gatherAssertTypes(__DIR__ . '/data/macros-php-81.php');
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

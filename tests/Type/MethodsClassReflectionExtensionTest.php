<?php

namespace Type;

class MethodsClassReflectionExtensionTest extends \PHPStan\Testing\TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__.'/data/macros.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/redirect-response.php');
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

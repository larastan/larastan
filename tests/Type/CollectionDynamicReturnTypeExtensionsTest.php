<?php

declare(strict_types=1);

namespace Tests\Type;

class CollectionDynamicReturnTypeExtensionsTest extends \PHPStan\Testing\TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__.'/data/collection-helper.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/collection-make-static.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/collection-stubs.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/collection-generic-static-methods.php');
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
        return [__DIR__.'/../phpstan-tests.neon'];
    }
}

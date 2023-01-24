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

        if (version_compare(LARAVEL_VERSION, '9.48.0', '<')) {
            yield from $this->gatherAssertTypes(__DIR__.'/data/collection-generic-static-methods.php');
        } else {
            yield from $this->gatherAssertTypes(__DIR__.'/data/collection-generic-static-methods-l948.php');
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
        return [__DIR__.'/../phpstan-tests.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPStan\Testing\PHPStanTestCase;
use ReflectionClass;

use function array_merge;
use function dirname;

class BleedingEdgeIntegrationTest extends IntegrationTest
{
    /** @return iterable<mixed> */
    public static function dataIntegrationTests(): iterable
    {
        yield [
            __DIR__ . '/data/bleeding-edge/collections.php',
            [
                31 => ['Parameter #1 ...$values of method Illuminate\Support\Collection<int,int>::push() expects int, string given.'],
            ],
        ];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        // PHPStan's own bleedingEdge.neon includes the config through a phar alias that only exists when the phar is executed.
        $pharRoot = dirname((string) (new ReflectionClass(PHPStanTestCase::class))->getFileName(), 3);

        return array_merge(parent::getAdditionalConfigFiles(), [$pharRoot . '/conf/bleedingEdge.neon']);
    }
}

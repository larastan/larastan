<?php

namespace Unit;

use PHPStan\Type\Type;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;

class PHPStanTypeComparator extends Comparator
{
    public function accepts($expected, $actual): bool
    {
        return $expected instanceof Type && $actual instanceof Type;
    }

    /**
     * @param  Type  $expected
     * @param  Type  $actual
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false): void
    {
        if (! $expected->equals($actual)) {
            throw new ComparisonFailure(
                $expected,
                $actual,
                $this->exporter->export($expected),
                $this->exporter->export($actual),
                false,
                'Failed asserting that two Type objects are equal.',
            );
        }
    }
}

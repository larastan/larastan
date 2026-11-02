<?php

namespace ArrMapStub;

use Illuminate\Support\Arr;

use function PHPStan\Testing\assertType;

/**
 * @param array<string, int> $items
 */
function test(array $items, string $label): void
{
    assertType('array<string, string>', Arr::map($items, static function (int $value, string $key) use ($label): string {
        return $label;
    }));

    assertType('array<string, non-empty-array<string, int>>', Arr::map($items, static function ($value, $key) {
        assertType('int', $value);
        assertType('string', $key);

        return [$key => $value];
    }));

    assertType('array<string, string>', Arr::map($items, static fn ($value) => (string) $value));
    assertType('array<string, string>', Arr::map($items, 'strval'));
}

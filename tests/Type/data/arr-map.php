<?php

namespace ArrMapStub;

use App\User;
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

/**
 * @param array<string, int> $items
 * @param list<User> $users
 */
function testMapWithKeys(array $items, array $users): void
{
    assertType('array<string, string>', Arr::mapWithKeys($items, static function ($value, $key) {
        assertType('int', $value);
        assertType('string', $key);

        return [$key => (string) $value];
    }));

    assertType('array<int, string>', Arr::mapWithKeys($items, static fn (int $value, string $key): array => [$value => $key]));

    assertType("array<'total', int>", Arr::mapWithKeys($items, static fn (int $value): array => ['total' => $value]));
    assertType('array<string, App\User>', Arr::mapWithKeys($users, static fn (User $user): array => [$user->email => $user]));
}

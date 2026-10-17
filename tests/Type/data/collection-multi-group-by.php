<?php

declare(strict_types=1);

namespace CollectionMultiGroupBy;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

use function PHPStan\Testing\assertType;

/**
 * @param SupportCollection<string, array{department: string, region: string, city: string}> $rows
 * @param EloquentCollection<int, User> $users
 */
function test(SupportCollection $rows, EloquentCollection $users, bool $preserveKeys): void
{
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>>',
        $rows->groupBy(['department', 'region']),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>>>',
        $rows->groupBy(['department', 'region', 'city']),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<string, array{department: string, region: string, city: string}>>>',
        $rows->groupBy(['department', 'region'], preserveKeys: true),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int|string, array{department: string, region: string, city: string}>>>',
        $rows->groupBy(['department', 'region'], $preserveKeys),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<string, array{department: string, region: string, city: string}>>>',
        $rows->groupBy(groupBy: ['department', 'city'], preserveKeys: true),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>>',
        $rows->groupBy([fn (array $record): string => $record['department'], 'city']),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Database\Eloquent\Collection<int, App\User>>>',
        $users->groupBy(['id', 'name']),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Database\Eloquent\Collection<int, App\User>>>>',
        $users->groupBy(['id', 'name', 'email']),
    );
}

/**
 * @param SupportCollection<string, array{department: string, region: string, city: string}> $rows
 * @param list<string> $dynamicSelectors
 * @param array{0: string, 1?: string} $optionalSelectors
 */
function dynamicSelectors(
    SupportCollection $rows,
    array $dynamicSelectors,
    array $optionalSelectors,
): void {
    // The existing stub is intentionally preserved for non-constant groups.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $rows->groupBy($dynamicSelectors),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $rows->groupBy($optionalSelectors),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $rows->groupBy(['department']),
    );
}

final class GroupingCallback
{
    /** @param array{department: string, region: string, city: string} $record */
    public static function byDepartment(array $record): string
    {
        return $record['department'];
    }
}

/** @param SupportCollection<string, array{department: string, region: string, city: string}> $rows */
function arrayCallableIsOneSelector(SupportCollection $rows): void
{
    // Laravel's useAsCallable() handles this as one callback, not two levels.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $rows->groupBy([GroupingCallback::class, 'byDepartment']),
    );
}

// A class-string/method pair can be a runtime callback, even if static
// analysis cannot prove the method exists. Never assert two grouping levels.
/**
 * @param SupportCollection<string, array{department: string, region: string, city: string}> $rows
 * @param class-string $candidate
 */
function potentiallyCallablePair(SupportCollection $rows, string $candidate): void
{
    assertType(
        'Illuminate\\Support\\Collection<(int|string), Illuminate\\Support\\Collection<int, mixed>>',
        $rows->groupBy([$candidate, 'byDepartment']),
    );
}

/** @param SupportCollection<string, array{department: string, region: string, city: string}> $rows */
function selectorDepthBoundary(SupportCollection $rows): void
{
    // MAX_GROUPS is intentionally eight. Verify the exact final boundary.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>>>>>>>>',
        $rows->groupBy(['department', 'region', 'city', 'department', 'region', 'city', 'department', 'region']),
    );

    // Nine selectors must keep the original Laravel/PHPStan fallback type.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, mixed>>',
        $rows->groupBy(['department', 'region', 'city', 'department', 'region', 'city', 'department', 'region', 'city']),
    );
}

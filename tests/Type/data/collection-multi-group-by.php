<?php

declare(strict_types=1);

namespace CollectionMultiGroupBy;

use App\Account;
use App\AccountCollection;
use App\NonGenericCollection;
use App\OnlyValueGenericCollection;
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
        'Illuminate\Support\Collection<(int|string), Illuminate\Database\Eloquent\Collection<(int|string), Illuminate\Database\Eloquent\Collection<int, App\User>>>',
        $users->groupBy(['id', 'name']),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Database\Eloquent\Collection<(int|string), Illuminate\Database\Eloquent\Collection<int, App\User>>>>',
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
    // Unknown depths must not promise Eloquent-only methods or integer group keys.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $rows->groupBy($dynamicSelectors),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $rows->groupBy($optionalSelectors),
    );

    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>',
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
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
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
        'Illuminate\\Support\\Collection<(int|string), Illuminate\\Support\\Collection<(int|string), mixed>>',
        $rows->groupBy([$candidate, 'byDepartment']),
    );
}

/** @param SupportCollection<string, array{department: string, region: string, city: string}> $rows */
function selectorDepthBoundary(SupportCollection $rows): void
{
    // Both sides of the former eight-selector boundary remain precise.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>>>>>>>>',
        $rows->groupBy(['department', 'region', 'city', 'department', 'region', 'city', 'department', 'region']),
    );

    // Depth comes from the constant array, without an arbitrary upper bound.
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, array{department: string, region: string, city: string}>>>>>>>>>>',
        $rows->groupBy(['department', 'region', 'city', 'department', 'region', 'city', 'department', 'region', 'city']),
    );
}

/**
 * @template TKey of array-key
 * @template TValue
 * @extends SupportCollection<TKey, TValue>
 */
class CustomSupportCollection extends SupportCollection
{
}

/**
 * @template TKey of array-key
 * @template TValue
 * @extends SupportCollection<TKey, TValue>
 */
class OverriddenGroupingCollection extends SupportCollection
{
    /** @return static<int, string> */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        return new static(['overridden']);
    }
}

/**
 * @param CustomSupportCollection<string, User> $support
 * @param AccountCollection<string, Account> $accounts
 * @param OnlyValueGenericCollection<User> $valueOnly
 * @param OverriddenGroupingCollection<string, User> $overridden
 */
function customCollections(
    CustomSupportCollection $support,
    AccountCollection $accounts,
    OnlyValueGenericCollection $valueOnly,
    NonGenericCollection $nonGeneric,
    OverriddenGroupingCollection $overridden,
): void {
    assertType(
        'CollectionMultiGroupBy\CustomSupportCollection<(int|string), CollectionMultiGroupBy\CustomSupportCollection<(int|string), CollectionMultiGroupBy\CustomSupportCollection<string, App\User>>>',
        $support->groupBy(['id', 'name'], true),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), App\AccountCollection<(int|string), App\AccountCollection<int, App\Account>>>',
        $accounts->groupBy(['id', 'name']),
    );
    assertType(
        'App\AccountCollection<(int|string), App\AccountCollection<string, App\Account>>',
        $accounts->groupBy(['name'], true),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $valueOnly->groupBy(['id', 'name']),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $nonGeneric->groupBy(['id', 'name']),
    );
    assertType('CollectionMultiGroupBy\OverriddenGroupingCollection<int, string>', $overridden->groupBy(['id', 'name']));
}

/**
 * @param SupportCollection<string, User> $rows
 * @param EloquentCollection<string, User> $users
 * @param list<string> $keys
 * @param mixed $preserve
 */
function keyPreservationAndFallback(SupportCollection $rows, EloquentCollection $users, array $keys, $preserve): void
{
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<string, App\User>>>',
        $rows->groupBy(preserveKeys: true, groupBy: ['id', 'name']),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<string, App\User>>>',
        $rows->groupBy(['id', 'name'], 1),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int, App\User>>>',
        $rows->groupBy(['id', 'name'], 0),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<int|string, App\User>>>',
        $rows->groupBy(['id', 'name'], $preserve),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $users->groupBy($keys),
    );
    assertType(
        'Illuminate\Database\Eloquent\Collection<(int|string), Illuminate\Database\Eloquent\Collection<int, App\User>>',
        $users->groupBy(['name']),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $rows->groupBy([]),
    );
}

/**
 * @template TValue
 * @template TKey of array-key
 * @extends SupportCollection<TKey, TValue>
 */
class ReversedCollection extends SupportCollection
{
}

/**
 * @template TUnused
 * @template TValue
 * @extends SupportCollection<string, TValue>
 */
class FixedKeyCollection extends SupportCollection
{
}

/**
 * @param ReversedCollection<User, string> $reversed
 * @param FixedKeyCollection<string, User> $fixed
 */
function nonCanonicalTemplates(ReversedCollection $reversed, FixedKeyCollection $fixed): void
{
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $reversed->groupBy(['id', 'name']),
    );
    assertType(
        'Illuminate\Support\Collection<(int|string), Illuminate\Support\Collection<(int|string), mixed>>',
        $fixed->groupBy(['id', 'name']),
    );
}

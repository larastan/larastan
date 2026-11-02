<?php

namespace BleedingEdgeCollectionUsageInference;

use App\User;
use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

/** @param Collection<int, int> $ints */
function takeInts(Collection $ints): void
{
}

/**
 * @param iterable<User>          $users
 * @param Collection<int, string> $names
 */
function emptyCollections(iterable $users, Collection $names, bool $flag): void
{
    $sent = collect();
    assertType('Illuminate\Support\Collection<int, int>', $sent);
    takeInts($sent);

    $sentEmptyArray = collect([]);
    assertType('Illuminate\Support\Collection<int, int>', $sentEmptyArray);
    takeInts($sentEmptyArray);

    $pushed = collect();
    foreach ($users as $user) {
        $pushed->push($user->name);
    }

    assertType('Illuminate\Support\Collection<int, string>', $pushed);

    $put = collect([]);
    $put->put('a', 1);
    $put->put('b', 2);
    assertType("Illuminate\Support\Collection<'a'|'b', 1|2>", $put);

    assertType('Illuminate\Support\Collection<int, string>', collect()->merge($names));
    assertType('Illuminate\Support\Collection<int, string>', $flag ? $names : collect());
}

function queries(int $id, string $role): void
{
    $ids = collect([1, 2, 3]);
    assertType('bool', $ids->contains($id));
    assertType('Illuminate\Support\Collection<0|1|2, int>', $ids);

    $partitioned = collect([1, 2, 3]);
    $partitioned->partition($id);
    $partitioned->some($id);
    assertType('Illuminate\Support\Collection<0|1|2, int>', $partitioned);

    $roles = collect(['admin', 'editor']);
    assertType('bool', $roles->contains($role));
    assertType("Illuminate\Support\Collection<0|1, 'admin'|'editor'>", $roles);

    $rejected = collect([1, 2, 3]);
    assertType('Illuminate\Support\Collection<0|1|2, int>', $rejected->reject($id));
}

<?php

namespace BleedingEdgeCollectionKeys;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

use function PHPStan\Testing\assertType;

/**
 * @param list<int>          $list
 * @param array<string, int> $map
 * @param array<mixed>       $mixed
 */
function renumberedKeys(array $list, array $map, array $mixed, User $user): void
{
    assertType('Illuminate\Support\Collection<0|1|2, 1|2|3>', collect([1, 2, 3]));

    assertType('Illuminate\Support\Collection<int, 1|2|3|4|5>', collect([1, 2, 3])->merge([4, 5]));
    assertType('Illuminate\Support\Collection<int, int>', collect([1, 2, 3])->merge($list));
    assertType("Illuminate\Support\Collection<'a'|'b'|'c', 1|2|3>", collect(['a' => 1, 'b' => 2])->merge(['c' => 3]));
    assertType('Illuminate\Support\Collection<string, int>', collect(['a' => 1, 'b' => 2])->merge($map));
    assertType("Illuminate\Support\Collection<'a'|'b'|int, 1|2|4>", collect(['a' => 1, 'b' => 2])->merge([4]));
    assertType("Illuminate\Support\Collection<int, 'x'|'y'|'z'>", collect([5 => 'x', 9 => 'y'])->merge([1 => 'z']));
    assertType('Illuminate\Support\Collection<(int|string), mixed>', collect($mixed)->merge($mixed));
    assertType('Illuminate\Support\Collection<int, 1|2|3|4>', collect([1, 2, 3])->mergeRecursive([4]));

    assertType('Illuminate\Support\Collection<int, 1|2|3|4|5>', collect([1, 2, 3])->concat([4, 5]));
    assertType("Illuminate\Support\Collection<'a'|'b'|int, 1|2|3>", collect(['a' => 1, 'b' => 2])->concat(['c' => 3]));

    assertType('Illuminate\Support\Collection<int, 1|2|3>', collect([1, 2, 3])->flatMap(fn (int $value) => [$value, $value]));
    assertType("Illuminate\Support\Collection<'k1'|'k2'|'k3', 1|2|3>", collect([1, 2, 3])->flatMap(fn (int $value) => ['k' . $value => $value]));

    assertType('Illuminate\Support\Collection<int, 1|2|3>', collect([1, 2, 3])->multiply(2));
    assertType("Illuminate\Support\Collection<int, 'x'|'y'>", collect([5 => 'x', 9 => 'y'])->shuffle());
    assertType("Illuminate\Support\Collection<int, Illuminate\Support\Collection<int, 'x'|'y'>>", collect([5 => 'x', 9 => 'y'])->split(2));
    assertType("Illuminate\Support\Collection<int, Illuminate\Support\Collection<'a'|'b', 1|2>>", collect(['a' => 1, 'b' => 2])->split(2));

    assertType('Illuminate\Support\LazyCollection<int, 1|2|3|4|5>', LazyCollection::make([1, 2, 3])->merge([4, 5]));
    assertType('Illuminate\Support\LazyCollection<int, 1|2|3|4|5>', LazyCollection::make([1, 2, 3])->concat([4, 5]));
    assertType('Illuminate\Support\LazyCollection<int, 1|2|3>', LazyCollection::make([1, 2, 3])->flatMap(fn (int $value) => [$value, $value]));
    assertType('Illuminate\Support\LazyCollection<int, 1|2|3>', LazyCollection::make([1, 2, 3])->multiply(2));

    assertType('Illuminate\Database\Eloquent\Collection<0, App\User>', new EloquentCollection([$user]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new EloquentCollection([$user]))->merge([$user]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new EloquentCollection([$user]))->concat([$user]));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\User>', (new EloquentCollection([$user]))->flatMap(fn (User $user) => [$user, $user]));
}

function mutations(): void
{
    $pushed = collect([1, 2, 3]);
    $pushed->push(4);
    assertType('Illuminate\Support\Collection<int, 1|2|3|4>', $pushed);

    $added = collect([1, 2, 3]);
    $added->add(4);
    assertType('Illuminate\Support\Collection<int, 1|2|3|4>', $added);

    $prepended = collect([1, 2, 3]);
    $prepended->prepend(0);
    assertType('Illuminate\Support\Collection<int, 0|1|2|3>', $prepended);

    $prependedWithKey = collect(['a' => 1]);
    $prependedWithKey->prepend(0, 'b');
    assertType("Illuminate\Support\Collection<'a'|'b', 0|1>", $prependedWithKey);

    $unshifted = collect([1, 2, 3]);
    $unshifted->unshift(0);
    assertType('Illuminate\Support\Collection<int, 0|1|2|3>', $unshifted);

    $shifted = collect([5 => 'x', 9 => 'y']);
    $shifted->shift();
    assertType("Illuminate\Support\Collection<int, 'x'|'y'>", $shifted);

    $shiftedStringKeys = collect(['a' => 1, 'b' => 2]);
    $shiftedStringKeys->shift();
    assertType("Illuminate\Support\Collection<'a'|'b', 1|2>", $shiftedStringKeys);

    $spliced = collect([5 => 'x', 9 => 'y']);
    assertType("Illuminate\Support\Collection<int, 'x'|'y'>", $spliced->splice(1));
    assertType("Illuminate\Support\Collection<int, 'x'|'y'>", $spliced);

    $put = collect(['a' => 1, 'b' => 2]);
    $put->getOrPut('c', 3);
    assertType("Illuminate\Support\Collection<'a'|'b'|'c', 1|2|3>", $put);
}

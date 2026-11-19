<?php

namespace MacrosProbe;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function PHPStan\Testing\assertType;

/**
 * @param Collection<int, User> $users
 * @param Builder<User> $q
 */
function test(Collection $users, Builder $q): void
{
    assertType("'literal'", $users->probeUntyped());
    assertType('Illuminate\Support\Collection<int, decimal-int-string>', $users->probeNativeNarrowed());
    assertType('Illuminate\Support\Collection<int, App\User>', $users->probeThisGeneric());
    assertType('App\User|null', $users->probeThisFirst());
    assertType('Illuminate\Support\Collection<int, int>', $users->probeThisKeys());
    assertType('Illuminate\Support\Collection<*NEVER*, *NEVER*>', $users->probeStatic());
    assertType('Illuminate\Support\Collection<*NEVER*, *NEVER*>', Collection::probeStatic());
    assertType('array{int, mixed}', $users->probeParam(1, 'a'));
    assertType('mixed', $users->probeRecursive(3));
    assertType('App\User|null', $users->probeCallsOther());
    assertType('mixed', $users->probeUse());
    assertType('mixed', $users->probeSameLineA());
    assertType('int', $users->probeFirstClass('abc'));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $q);
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', User::query());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $q->probeBuilderThis());
    assertType('App\User|null', $q->probeBuilderFirst());
    assertType('App\User|null', User::query()->probeBuilderFirst());
    assertType('Illuminate\Database\Eloquent\Model|null', User::probeBuilderFirst());
    assertType('Illuminate\Support\Stringable', Str::probeStr('a'));
    assertType('Illuminate\Support\Collection<int, App\User>', $users->probeTypedThis());
    assertType('array<int, App\User>', $users->probeTypedArray());
    assertType('App\User|null', $users->probeTypedMixed());
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $q->probeTypedBuilder());
    assertType('App\User|null', $q->probeTypedModel());
    assertType('string', Str::trimMacro('a'));
    assertType('int', Str::probeLen('a'));
    assertType('array|int', Str::probeCount('a'));
    assertType('string', Str::trimMacro('a'));
}

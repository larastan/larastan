<?php

namespace Passthru;

use App\User;

use function PHPStan\Testing\assertType;

function test() {
    assertType('int<0, max>', User::query()->getCountForPagination());
    assertType('mixed', User::query()->aggregate('sum', ['id']));
    assertType('mixed', User::query()->doesntExistOr(fn () => 'x'));
    assertType('mixed', User::query()->existsOr(fn () => 'x'));
    assertType('Illuminate\Support\Collection', User::query()->explain());
    assertType('array{select: list<mixed>, from: list<mixed>, join: list<mixed>, where: list<mixed>, groupBy: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>, unionOrder: list<mixed>}', User::query()->getRawBindings());
    assertType('string', User::query()->implode('name'));
    assertType('int', User::query()->insertOrIgnoreUsing(['name'], 'select 1'));
    assertType('float|int', User::query()->numericAggregate('sum', ['id']));
    assertType('mixed', User::query()->rawValue('max(id)'));
}

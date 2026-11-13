<?php

namespace Passthru;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

function test() {
    assertType('int<0, max>', User::query()->getCountForPagination());
    assertType('mixed', User::query()->aggregate('sum', ['id']));
    assertType('mixed', User::query()->doesntExistOr(fn () => 'x'));
    assertType('mixed', User::query()->existsOr(fn () => 'x'));
    assertType('Illuminate\Support\Collection', User::query()->explain());
    assertType('array{select: list<mixed>, from: list<mixed>, join: list<mixed>, where: list<mixed>, groupBy: list<mixed>, having: list<mixed>, order: list<mixed>, union: list<mixed>, unionOrder: list<mixed>}', User::query()->getRawBindings());
    assertType('string', User::query()->implode('name'));
    assertType('Illuminate\Support\Collection', User::query()->insertOrIgnoreReturning([]));
    assertType('int', User::query()->insertOrIgnoreUsing(['name'], 'select 1'));
    assertType('float|int', User::query()->numericAggregate('sum', ['id']));
    assertType('mixed', User::query()->rawValue('max(id)'));
}

class Post extends Model
{
}

/** @extends Builder<Post> */
class PassthruBuilder extends Builder
{
    public function test(): void
    {
        assertType('Illuminate\Support\Collection', $this->insertOrIgnoreReturning([]));
    }
}

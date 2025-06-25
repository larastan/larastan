<?php

namespace Bug69;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

use Illuminate\Database\Eloquent\Model;
use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

/**
 * @template T of Model
 * @extends Builder<T>
 */
class B extends Builder {}


class A extends Model {
    /** @return B<A> */
    public function newEloquentBuilder($query): B
    {
        return new B($query);
    }
}
class C extends Model {
}

/** @template BaseModel of Model */
abstract class BaseRepository
{
    /** @return BaseModel */
    abstract public function model();

    /**
     * @param array<string,scalar> $search
     * @return Builder<BaseModel>
     */
    public function query(array $search = []): Builder
    {
        assertType('Illuminate\Database\Eloquent\Builder<BaseModel of Illuminate\Database\Eloquent\Model (class Bug69\BaseRepository, argument)>', $this->model()::query());
        assertType('BaseModel of Illuminate\Database\Eloquent\Model (class Bug69\BaseRepository, argument)|null', $this->model()::query()->first());
        return $this->model()::query();
    }
}

function returnUnion(): A|C {
    if (rand(0, 100) > 50) return new C;

    return new A;
}

function test(): void
{
    assertType('Bug69\B<Bug69\A|Bug69\C>', returnUnion()::query());
}

<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ModelExtension
{
    /**
     * @return iterable<\App\User>|\Illuminate\Database\Eloquent\Collection
     */
    public function testAll()
    {
        return User::all();
    }

    public function testReturnThis(): Builder
    {
        $user = User::join('tickets.tickets', 'tickets.tickets.id', '=', 'tickets.sale_ticket.ticket_id')
            ->where(['foo' => 'bar']);

        return $user;
    }

    public function testWhere(): Builder
    {
        return (new Thread)->where(['foo' => 'bar']);
    }

    public function testStaticWhere(): Builder
    {
        return Thread::where(['foo' => 'bar']);
    }

    public function testDynamicWhere(): Builder
    {
        return (new Thread)->whereFoo(['bar']);
    }

    public function testStaticDynamicWhere(): Builder
    {
        return Thread::whereFoo(['bar']);
    }

    public function testWhereIn(): Builder
    {
        return (new Thread)->whereIn('id', [1, 2, 3]);
    }

    public function testIncrement() : int
    {
        /** @var User $user */
        $user = new User;

        return $user->increment('counter');
    }

    public function testDecrement() : int
    {
        /** @var User $user */
        $user = new User;

        return $user->decrement('counter');
    }

    public function testFind() : ?User
    {
        return User::find(1);
    }

    public function testFindCanReturnCollection() : ?Collection
    {
        /** @var Collection $users */
        $users = User::find([1, 2, 3]);

        return $users;
    }

    /** @return iterable<User>|null */
    public function testFindCanReturnCollectionWithAnnotation()
    {
        return User::find([1, 2, 3]);
    }

    /** @return iterable<User> */
    public function testFindMany()
    {
        return User::findMany([1, 2, 3]);
    }

    public function testFindOrFail() : User
    {
        return User::findOrFail(1);
    }

    public function testFindOrFailCanReturnCollection() : Collection
    {
        /** @var Collection $users */
        $users = User::findOrFail([1, 2, 3]);

        return $users;
    }

    public function testFirst() : ?User
    {
        return User::first();
    }

//    public function testMake() : User
//    {
//        return User::make([]);
//    }

    public function testCreate() : User
    {
        return User::create([]);
    }

    public function testForceCreate() : User
    {
        return User::forceCreate([]);
    }

    public function testFindOrNew() : User
    {
        return User::findOrNew([]);
    }

    public function testFirstOrNew() : User
    {
        return User::firstOrNew([]);
    }

    public function testUpdateOrCreate() : User
    {
        return User::updateOrCreate([]);
    }

    public function testFirstOrCreate(): User
    {
        return User::firstOrCreate([]);
    }

    public function testScope() : Builder
    {
        return Thread::valid();
    }

    public function testMacro(Builder $query) : Builder
    {
        return $query->macro('customMacro', function () {
        });
    }

    public function testChainingCollectionMethodsOnModel() : Collection
    {
        return User::findOrFail([1, 2, 3])->makeHidden('foo');
    }

    public function testFirstOrFailWithChain() : User
    {
        return User::with('foo')
            ->where('foo', 'bar')
            ->orWhere('bar', 'baz')
            ->firstOrFail();
    }

    public function testFirstWithChain() : ?User
    {
        return User::with('foo')
            ->where('foo', 'bar')
            ->orWhere('bar', 'baz')
            ->first();
    }

    public function testFindOnVariableClassName() : ?User
    {
        $class = foo();

        return $class::query()->find(5);
    }

    /** @return iterable<Thread>&Collection */
    public function testCustomMethodsStartingWithFind()
    {
        return Thread::findAllFooBarThreads();
    }
}

function foo() : string {}

class Thread extends Model
{
    public function scopeValid(Builder $query) : Builder
    {
        return $query->where('valid', true);
    }

    public static function testFindOnStaticSelf() : ?Thread
    {
        return self::valid()->first();
    }

    public static function testFindOnStatic() : ?Thread
    {
        return static::valid()->first();
    }

    /** @return iterable<Thread>&Collection */
    public static function findAllFooBarThreads()
    {
        return self::query()->where('foo', 'bar')->get();
    }
}

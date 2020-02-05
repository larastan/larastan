<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class ModelExtension
{
    /**
     * @return \Illuminate\Database\Eloquent\Collection<User>
     */
    public function testAll()
    {
        return User::all();
    }

    public function testAllFirst(): ?User
    {
        return User::all()->first();
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

    public function testIncrement(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->increment('counter');
    }

    public function testDecrement(): int
    {
        /** @var User $user */
        $user = new User;

        return $user->decrement('counter');
    }

    public function testFind(): ?User
    {
        return User::find(1);
    }

    /**
     * @return Collection<\App\User>|null
     */
    public function testFindCanReturnCollection(): ?Collection
    {
        return User::find([1, 2, 3]);
    }

    /** @return Collection<User>|null */
    public function testFindCanReturnCollectionWithAnnotation()
    {
        return User::find([1, 2, 3]);
    }

    /** @return Collection<User>|null */
    public function testFindMany()
    {
        return User::findMany([1, 2, 3]);
    }

    public function testFindOrFail(): User
    {
        return User::findOrFail(1);
    }

    /**
     * @return Collection<\App\User>
     */
    public function testFindOrFailCanReturnCollection(): Collection
    {
        /** @var Collection<\App\User> $users */
        $users = User::findOrFail([1, 2, 3]);

        return $users;
    }

    public function testFirst(): ?User
    {
        return User::first();
    }

    public function testMake(): User
    {
        return User::make([]);
    }

    public function testCreate(): User
    {
        return User::create([]);
    }

    public function testForceCreate(): User
    {
        return User::forceCreate([]);
    }

    public function testFindOrNew(): User
    {
        return User::findOrNew([]);
    }

    public function testFirstOrNew(): User
    {
        return User::firstOrNew([]);
    }

    public function testUpdateOrCreate(): User
    {
        return User::updateOrCreate([]);
    }

    public function testFirstOrCreate(): User
    {
        return User::firstOrCreate([]);
    }

    public function testScope(): Builder
    {
        return Thread::valid();
    }

    public function testMacro(Builder $query): void
    {
        $query->macro('customMacro', function () {
        });
    }

    /**
     * @return Collection<User>
     */
    public function testChainingCollectionMethodsOnModel(): Collection
    {
        return User::findOrFail([1, 2, 3])->makeHidden('foo');
    }

    public function testCollectionMethodWillReturnUser(): ?User
    {
        return User::findOrFail([1, 2, 3])->makeHidden('foo')->first();
    }

    public function testFirstOrFailWithChain(): User
    {
        return User::with('foo')
            ->where('foo', 'bar')
            ->orWhere('bar', 'baz')
            ->firstOrFail();
    }

    public function testFirstWithChain(): ?User
    {
        return User::with('foo')
            ->where('foo', 'bar')
            ->orWhere('bar', 'baz')
            ->first();
    }

    /** @return Collection<User>|null */
    public function testFindWithCastingToArray(FormRequest $request): ?Collection
    {
        $requestData = $request->validated();

        return User::find((array) $requestData['user_ids']);
    }

    public function testFindWithCastingToInt(): ?User
    {
        return User::find((int) '1');
    }

    public function testCustomAccessorOnModels(): string
    {
        /** @var Thread $thread */
        $thread = Thread::findOrFail(5);

        return $thread->custom_property;
    }
}

function foo(): string
{
    return 'foo';
}

class Thread extends Model
{
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('valid', true);
    }

    public static function testFindOnStaticSelf(): ?Thread
    {
        return self::valid()->first();
    }

    public static function testFindOnStatic(): ?Thread
    {
        return static::valid()->first();
    }

    /** @return iterable<Thread>&Collection */
    public static function findAllFooBarThreads()
    {
        return self::query()->where('foo', 'bar')->get();
    }

    public function getCustomPropertyAttribute(): string
    {
        return 'thread';
    }
}

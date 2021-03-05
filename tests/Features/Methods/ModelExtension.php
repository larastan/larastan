<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /** @return Builder<User> */
    public function testReturnThis(): Builder
    {
        $user = User::join('tickets.tickets', 'tickets.tickets.id', '=', 'tickets.sale_ticket.ticket_id')
            ->where(['email' => 'bar']);

        return $user;
    }

    /** @return Builder<Thread> */
    public function testWhere(): Builder
    {
        return (new Thread)->where(['name' => 'bar']);
    }

    /** @return Builder<Thread> */
    public function testStaticWhere(): Builder
    {
        return Thread::where(['name' => 'bar']);
    }

    /** @return Builder<Thread> */
    public function testDynamicWhere(): Builder
    {
        return (new Thread)->whereName(['bar']);
    }

    /** @return Builder<Thread> */
    public function testStaticDynamicWhere(): Builder
    {
        return Thread::whereName(['bar']);
    }

    /** @return Builder<Thread> */
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

    public function testFindOnGenericModel(Model $model): ?Model
    {
        return $model::find(1);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public function testFindOnModelClassString(string $modelClass): ?Model
    {
        return $modelClass::find(1);
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
            ->where('email', 'bar')
            ->orWhere('name', 'baz')
            ->firstOrFail();
    }

    public function testFirstWithChain(): ?User
    {
        return User::with('foo')
            ->where('email', 'bar')
            ->orWhere('name', 'baz')
            ->first();
    }

    public function testWithAcceptsArrayOfClosures(): ?User
    {
        return User::with(['accounts' => function (HasMany $relation) {
            return $relation->where('active', true);
        }])->find(1);
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

    public function testFirstOrCreateWithRelation(User $user): Account
    {
        return $user->accounts()->firstOrCreate([]);
    }

    /** @return Builder<User> */
    public function testSoftDeletesOnlyTrashed(): Builder
    {
        return User::onlyTrashed();
    }

    /** @return Builder<User> */
    public function testSoftDeletesWithTrashed(): Builder
    {
        return User::withTrashed(false);
    }

    public function testFindOrFailWithSoftDeletesTrait(): User
    {
        return User::onlyTrashed()->findOrFail(5);
    }

    public function testFirstWhere(): ?User
    {
        return User::firstWhere(['email' => 'foo@bar.com']);
    }

    public function testFirstWhereWithBuilder(): ?User
    {
        return User::query()->where('name', 'bar')->firstWhere(['email' => 'foo@bar.com']);
    }

    public function testWithOnModelVariable(User $user): ?User
    {
        return $user->with('accounts')->find(1);
    }

    public function testMultipleWithOnModelVariable(User $user): ?User
    {
        return $user->with('accounts')->with('group')->find(1);
    }

    /** @phpstan-return Builder<User> */
    public function testLockForUpdate(): Builder
    {
        return User::where('id', '>', 5)->lockForUpdate();
    }

    /** @phpstan-return Builder<User> */
    public function testSharedLock(): Builder
    {
        return User::where('id', '>', 5)->sharedLock();
    }

    /** @phpstan-return Builder<User> */
    public function testNewQuery(): Builder
    {
        return User::where('id', '>', 5)->newQuery();
    }

    public function testOrWhereWithQueryExpression(): ?User
    {
        return User::with('foo')
            ->orWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
            ->first();
    }

    public function testWhereWithQueryExpression(): ?User
    {
        return User::with('foo')
            ->where(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%')
            ->first();
    }

    public function testFirstWhereWithQueryExpression(): ?User
    {
        return User::with('foo')
            ->firstWhere(\Illuminate\Support\Facades\DB::raw('name'), 'like', '%john%');
    }

    public function testValueWithQueryExpression(): ?string
    {
        return User::with('foo')
            ->value(\Illuminate\Support\Facades\DB::raw('name'));
    }
}

function foo(): string
{
    return 'foo';
}

/**
 * @property string $name
 */
class Thread extends Model
{
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('name', true);
    }

    public static function testFindOnStaticSelf(): ?Thread
    {
        return self::query()->where('name', 'bar')->first();
    }

    public function getCustomPropertyAttribute(): string
    {
        return 'thread';
    }

    /** @phpstan-return mixed[] */
    public static function asSelect(): array
    {
        return self::all()->pluck('name', 'id')->toArray();
    }

    public function methodUsingACustomMethodReturningRelation(): HasMany
    {
        return $this->customMethodReturningRelation();
    }

    public function customMethodReturningRelation(): HasMany
    {
        return $this->hasManyFromConnection('replica', User::class)
            ->where('status', '!=', 'deleted');
    }

    /**
     * @see https://github.com/nunomaduro/larastan/issues/562
     *
     * Allows use of different DB connections for relationships
     *
     * @param  string  $connection
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $localKey
     * @return HasMany
     */
    public function hasManyFromConnection(
        string $connection,
        string $related,
        string $foreignKey = null,
        string $localKey = null
    ): HasMany {
        $foreignKey = $foreignKey ?: $this->getForeignKey();
        $instance = new $related;
        $instance->setConnection($connection);
        $localKey = $localKey ?: $this->getKeyName();

        return new HasMany($instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey);
    }
}

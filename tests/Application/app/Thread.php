<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $name
 */
class Thread extends Model
{
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('name', true);
    }

    public static function testFirstOnStaticSelf(): ?\Tests\Features\Methods\Thread
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

    /**
     * @phpstan-return Collection<int, User>
     */
    public static function methodReturningCollectionOfAnotherModel(): Collection
    {
        return new Collection([]);
    }

    /**
     * @phpstan-return Collection<int, Thread>|Thread
     */
    public static function methodReturningUnionWithCollection()
    {
        return new Collection([]);
    }

    /**
     * @phpstan-return Collection<int, User>|User
     */
    public static function methodReturningUnionWithCollectionOfAnotherModel()
    {
        return new Collection([]);
    }
}

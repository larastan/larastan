<?php

namespace Bug2283;

use App\Account;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function PHPStan\Testing\assertType;

class Comment extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<App\User, $this(Bug2283\Comment)>', $this->belongsTo(User::class)
            ->when(true, fn($query) => $query));

        return $this->belongsTo(User::class)
            ->when(true, fn($query) => $query);
    }
}

function test(User $user, bool $condition): void
{
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $user->accounts()->when($condition, fn ($query) => $query->where('active', '1')));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $user->accounts()->unless($condition, fn ($query) => $query->where('active', '1')));
    assertType('Illuminate\Database\Eloquent\Relations\HasMany<App\Account, App\User>', $user->accounts()->tap(fn ($query) => $query->where('active', '1')));
    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<App\Role, App\User, Illuminate\Database\Eloquent\Relations\Pivot, \'pivot\'>', $user->roles()->when($condition, fn ($query) => $query->where('name', 'admin')));
    assertType('int<0, max>', $user->accounts()->when($condition, fn ($query) => $query->count()));
    assertType('Illuminate\Database\Eloquent\Builder<App\User>', $user->accounts()->when($condition, fn ($query) => User::query()));
}

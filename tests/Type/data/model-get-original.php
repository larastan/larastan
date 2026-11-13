<?php

namespace ModelGetOriginal;

use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function PHPStan\Testing\assertType;

/**
 * @property string $status
 * @property-read User $owner
 * @property-read User $customer
 */
class Order extends Model
{
    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class);
    }

    public function test(): void
    {
        assertType('string', $this->getOriginal('status'));
    }
}

/** @param 'name'|'email' $nameOrEmail */
function test(User $user, User|Post $userOrPost, Order $order, string $key, string $nameOrEmail): void
{
    assertType('string', $user->getOriginal('name'));
    assertType('string', $user->getOriginal(key: 'name'));
    assertType('string', $user->getOriginal($nameOrEmail));
    assertType('bool', $user->getOriginal('blocked'));
    assertType('Carbon\Carbon|null', $user->getOriginal('email_verified_at'));
    assertType('App\Casts\BackedEnumeration', $user->getOriginal('backed_enum'));
    assertType('numeric-string|null', $user->getOriginal('nullable_decimal'));
    assertType('string', $user->getOriginal('only_available_with_accessor'));
    assertType('string', $user->getOriginal('propertyDefinedOnlyInAnnotation'));
    assertType('int<0, max>', $user->getOriginal('accounts_count'));
    assertType('int<0, max>', $userOrPost->getOriginal('id'));
    assertType('string', $order->getOriginal('status'));

    // Relations are not attributes
    assertType('mixed', $user->getOriginal('group'));
    assertType('mixed', $user->getOriginal('accounts'));
    assertType('mixed', $order->getOriginal('owner'));
    assertType('mixed', $order->getOriginal('customer'));

    assertType('mixed', $user->getOriginal('nonexistent'));
    assertType('mixed', $user->getOriginal($key));
    assertType('mixed', $user->getOriginal('name', 'default'));
    assertType('mixed', $user->getOriginal(...['name']));
    assertType('array<string, mixed>', $user->getOriginal());
}

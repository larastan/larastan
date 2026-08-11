<?php

namespace TraitInterfaceRelationCovariance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use function PHPStan\Testing\assertType;

class Comment extends Model {}

class Account extends Model {}

class User extends Model {}

interface HasComments
{
    /** @return MorphMany<Comment, static> */
    public function comments(): MorphMany;
}

interface HasAccounts
{
    /** @return HasMany<Account, static> */
    public function accounts(): HasMany;
}

interface HasOwner
{
    /** @return BelongsTo<User, static> */
    public function owner(): BelongsTo;
}

trait Commentable
{
    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

trait Accountable
{
    /** @return HasMany<Account, $this> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }
}

trait Ownable
{
    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

class Post extends Model implements HasComments, HasAccounts, HasOwner
{
    use Commentable;
    use Accountable;
    use Ownable;
}

function test(Post $post): void
{
    assertType(
        'Illuminate\Database\Eloquent\Relations\MorphMany<TraitInterfaceRelationCovariance\Comment, TraitInterfaceRelationCovariance\Post>',
        $post->comments()
    );
    assertType(
        'Illuminate\Database\Eloquent\Relations\HasMany<TraitInterfaceRelationCovariance\Account, TraitInterfaceRelationCovariance\Post>',
        $post->accounts()
    );
    assertType(
        'Illuminate\Database\Eloquent\Relations\BelongsTo<TraitInterfaceRelationCovariance\User, TraitInterfaceRelationCovariance\Post>',
        $post->owner()
    );
}

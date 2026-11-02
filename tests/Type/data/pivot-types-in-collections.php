<?php

namespace PivotTypesInCollections;

use App\Account;
use App\Post;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

use function PHPStan\Testing\assertType;

class Subscription extends Pivot
{
}

class Podcast extends Model
{
}

class Member extends Model
{
    /** @return BelongsToMany<Podcast, $this, Subscription, 'subscription'> */
    public function podcasts(): BelongsToMany
    {
        return $this->belongsToMany(Podcast::class)->as('subscription')->using(Subscription::class);
    }
}

function test(User $user, Post $post, Member $member, User|Account $union): void
{
    assertType('App\RoleCollection<int, App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', $user->roles);
    assertType('Illuminate\Database\Eloquent\Relations\Pivot', $user->roles->firstOrFail()->pivot);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Post&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', $user->posts);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Post&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', $union->posts);
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Tag&object{pivot: Illuminate\Database\Eloquent\Relations\MorphPivot}>', $post->tags);
    assertType('App\RoleCollection<int, App\Role&object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}>', $user->roles()->findMany([1, 2]));

    assertType('Illuminate\Database\Eloquent\Collection<int, object{subscription: PivotTypesInCollections\Subscription}&PivotTypesInCollections\Podcast>', $member->podcasts);
    assertType('PivotTypesInCollections\Subscription', $member->podcasts->firstOrFail()->subscription);
    assertType('Illuminate\Database\Eloquent\Collection<int, object{subscription: PivotTypesInCollections\Subscription}&PivotTypesInCollections\Podcast>', $member->podcasts()->findMany([1, 2]));

    // Relations without a pivot are unaffected.
    assertType('App\AccountCollection<int, App\Account>', $user->accounts);
}

/**
 * @template TModel of Model
 * @param  BelongsToMany<TModel, Member>  $relation
 */
function testTemplate(BelongsToMany $relation): void
{
    assertType('Illuminate\Database\Eloquent\Collection<int, object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}&TModel of Illuminate\Database\Eloquent\Model (function PivotTypesInCollections\testTemplate(), argument)>', $relation->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, object{pivot: Illuminate\Database\Eloquent\Relations\Pivot}&TModel of Illuminate\Database\Eloquent\Model (function PivotTypesInCollections\testTemplate(), argument)>', $relation->findMany([1, 2]));
}

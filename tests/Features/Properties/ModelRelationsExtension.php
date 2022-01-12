<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\Account;
use App\Address;
use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelRelationsExtension
{
    public function testModelWithRelationDefinedInTrait(Account $account): ?User
    {
        return $account->ownerRelation;
    }

    public function testAsArrayObjectCast(User $user): ArrayObject
    {
        return $user->options;
    }

    public function testAsArrayObjectCastCount(User $user): int
    {
        return count($user->options);
    }

    public function testAsCollectionCast(User $user): \Illuminate\Support\Collection
    {
        return $user->properties;
    }

    public function testAsCollectionCastCount(User $user): int
    {
        return count($user->properties);
    }

    /** @phpstan-return mixed */
    public function testAsCollectionCastElements(User $user)
    {
        return $user->properties->first();
    }

    public function testForeignIdFor(Address $address): int
    {
        return $address->user_id;
    }

    public function testForeignIdForName(Address $address): int
    {
        return $address->custom_foreign_id_for_name;
    }

    public function testForeignIdUUID(Address $address): string
    {
        return $address->address_id;
    }

    public function testForeignIdNullable(Address $address): ?string
    {
        return $address->nullable_address_id;
    }
}


<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\Properties;

use App\Address;
use App\User;
use ArrayObject;
use Illuminate\Support\Collection;

class ModelPropertyExtension
{
    public function testAsArrayObjectCast(User $user): ArrayObject
    {
        return $user->options;
    }

    public function testAsArrayObjectCastCount(User $user): int
    {
        return count($user->options);
    }

    public function testAsCollectionCast(User $user): Collection
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

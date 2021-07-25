<?php

declare(strict_types=1);

namespace Tests\Features\Laravel8\Properties;

use App\Account;
use App\Group;
use App\GuardedModel;
use App\Role;
use App\Thread;
use App\User;
use ArrayObject;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Support\Carbon;
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
}

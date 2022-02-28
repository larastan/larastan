<?php

declare(strict_types=1);

namespace Tests\Features\Properties\CarbonImmutable;

use App\Account;
use App\Address;
use App\Group;
use App\GuardedModel;
use App\Role;
use App\Team;
use App\Thread;
use App\User;
use ArrayObject;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ModelPropertyExtension
{
    /** @var User */
    private $user; // @phpstan-ignore-line

    /**
     * @return CarbonImmutable|null
     */
    public function testDateReturnType()
    {
        return $this->user->created_at;
    }

    public function testWriteToProperty(): void
    {
        $this->user->created_at = 'test';
        $this->user->created_at = now();
        $this->user->created_at = null;
        $this->user->created_at = CarbonImmutable::now();
    }

    public function testDateCast(User $user): ?CarbonImmutable
    {
        $user->email_verified_at = now();

        return $user->email_verified_at;
    }

    public function testNullablePropertyWithCast(User $user): void
    {
        $user->email_verified_at = null;
    }
}

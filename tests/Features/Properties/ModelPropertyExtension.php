<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\Account;
use App\Group;
use App\GuardedModel;
use App\Role;
use App\Thread;
use App\User;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Support\Carbon;

class ModelPropertyExtension
{
    /** @var User */
    private $user; // @phpstan-ignore-line

    /** @var Account */
    private $account; // @phpstan-ignore-line

    /** @var Role */
    private $role; // @phpstan-ignore-line

    /** @var Group */
    private $group; // @phpstan-ignore-line

    public function testPropertyReturnType(): int
    {
        return $this->user->id;
    }

    public function testBooleanProperty(): bool
    {
        return $this->user->blocked;
    }

    public function testBooleanAcceptsZero(): void
    {
        $this->user->blocked = 0;
    }

    public function testBooleanAcceptsOne(): void
    {
        $this->user->blocked = 1;
    }

    public function testBooleanAcceptsFalse(): void
    {
        $this->user->blocked = false;
    }

    /**
     * @return Carbon|BaseCarbon|null
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
        $this->user->created_at = BaseCarbon::now();
    }

    /** @return array<int, string> */
    public function testCast(): array
    {
        return $this->user->meta;
    }

    /** @return mixed */
    public function testKnownColumnNameWithUnknownType()
    {
        $this->user->unknown_column = 5;
        $this->user->unknown_column = 'foo';

        return $this->user->unknown_column;
    }

    public function testMigrationWithoutSchemaFacadeImport(): string
    {
        return $this->account->active;
    }

    public function testReadUUIDProperty(): string
    {
        return $this->role->id;
    }

    public function testWriteUUIDProperty(): bool
    {
        $role = new Role();
        $role->id = 'abcd-efgh-ijkl';

        return $role->save();
    }

    public function testReadIdPropertyWhenMigrationsCouldntBeRead(): int
    {
        return $this->group->id;
    }

    public function testWriteIdPropertyWhenMigrationsCouldntBeRead(): bool
    {
        $group = new Group();
        $group->id = 5;

        return $group->save();
    }

    public function testModelWithGuardedProperties(GuardedModel $guardedModel): string
    {
        return $guardedModel->name;
    }

    public function testCustomAccessorOnModels(Thread $thread): string
    {
        return $thread->custom_property;
    }

    public function testDateCast(User $user): ?BaseCarbon
    {
        $user->email_verified_at = now();

        return $user->email_verified_at;
    }

    public function testNullablePropertyWithCast(User $user): void
    {
        $user->email_verified_at = null;
    }
}

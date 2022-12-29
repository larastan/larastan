<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\Account;
use App\Address;
use App\Group;
use App\GuardedModel;
use App\Role;
use App\Team;
use App\Thread;
use App\User;
use App\ValueObjects\Favorites;
use ArrayObject;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use function PHPStan\Testing\assertType;

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

    /** @var Team */
    private $team; // @phpstan-ignore-line

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

    public function testReadIdPropertyWhenMigrationsCouldntBeReadAndKeyTypeIsString(): string
    {
        return $this->team->id;
    }

    public function testWriteIdPropertyWhenMigrationsCouldntBeReadAndKeyTypeIsString(): bool
    {
        $team = new Team();
        $team->id = 'five';

        return $team->save();
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
        assertType('string', $user->email);
        $user->email_verified_at = now();

        return $user->email_verified_at;
    }

    public function testNullablePropertyWithCast(User $user): void
    {
        $user->email_verified_at = null;
    }

    /** @return array<string> */
    public function testEncryptedArrayCast(User $user): array
    {
        return $user->allowed_ips;
    }

    public function testPrecisionDecimalCast(User $user): string
    {
        return $user->floatButRoundedDecimalString;
    }

    /** @return ArrayObject<array-key, mixed> */
    public function testAsArrayObjectCast(User $user): ArrayObject
    {
        return $user->options;
    }

    public function testAsArrayObjectCastCount(User $user): int
    {
        return count($user->options);
    }

    /** @return Collection<array-key, mixed> */
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

    public function testSoftDeletesCastDateTimeAndNullable(User $user): ?string
    {
        return $user->deleted_at?->format('d/m/Y');
    }

    public function testWriteToSoftDeletesColumn(): void
    {
        $this->user->deleted_at = 'test';
        $this->user->deleted_at = now();
        $this->user->deleted_at = null;
        $this->user->deleted_at = BaseCarbon::now();
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

    public function testForeignIdConstrained(Address $address): int
    {
        return $address->foreign_id_constrained;
    }

    public function testForeignIdConstrainedNullable(Address $address): ?int
    {
        return $address->nullable_foreign_id_constrained;
    }

    public function testCustomCast(): Favorites
    {
        return $this->user->favorites;
    }

    public function testInboundCast(): void
    {
        $this->user->secret = 'secret';
    }
}

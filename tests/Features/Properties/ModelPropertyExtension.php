<?php

declare(strict_types=1);

namespace Tests\Features\Properties;

use App\Account;
use App\Group;
use App\Role;
use App\User;
use Carbon\Carbon as BaseCarbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ModelPropertyExtension
{
    /** @var User */
    private $user;

    /** @var Account */
    private $account;

    /** @var Role */
    private $role;

    /** @var Group */
    private $group;

    public function testPropertyReturnType(): int
    {
        return $this->user->id;
    }

    public function testBooleanProperty(): bool
    {
        return $this->user->blocked;
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

    public function testKnownColumnNameWithUnknownType(): string
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

    /** @test */
    public function it_knows_name_of_primary_key_column(): void
    {
        $model = new CustomPrimaryKeyColumn();
        $primaryKey = $model->custom_primary_key_name;
    }
}

class CustomPrimaryKeyColumn extends Model
{
    protected $primaryKey = 'custom_primary_key_name';
}

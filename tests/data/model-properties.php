<?php

namespace ModelPropertiesIntegration;

use App\Group;
use App\Role;
use App\Team;
use App\User;
use Carbon\Carbon as BaseCarbon;
use function PHPStan\Testing\assertType;

function testBooleanAcceptsZero(User $user): void
{
    $user->blocked = 0;
}

function testBooleanAcceptsOne(User $user): void
{
    $user->blocked = 1;
}

function testBooleanAcceptsFalse(User $user): void
{
    $user->blocked = false;
}

function testWriteToProperty(User $user): void
{
    $user->created_at = 'test';
    $user->created_at = now();
    $user->created_at = null;
    $user->created_at = BaseCarbon::now();
}

/** @return mixed */
function testKnownColumnNameWithUnknownType(User $user)
{
    $user->unknown_column = 5;
    $user->unknown_column = 'foo';

    return $user->unknown_column;
}

function testWriteUUIDProperty(): bool
{
    $role = new Role();
    $role->id = 'abcd-efgh-ijkl';

    return $role->save();
}

function testWriteIdPropertyWhenMigrationsCouldntBeRead(): bool
{
    $group = new Group();
    $group->id = 5;

    return $group->save();
}

function testWriteIdPropertyWhenMigrationsCouldntBeReadAndKeyTypeIsString(): bool
{
    $team = new Team();
    $team->id = 'five';

    return $team->save();
}

function testDateCast(User $user): ?BaseCarbon
{
    assertType('string', $user->email);
    $user->email_verified_at = now();

    return $user->email_verified_at;
}

function testNullablePropertyWithCast(User $user): void
{
    $user->email_verified_at = null;
}

function testWriteToSoftDeletesColumn(User $user): void
{
    $user->deleted_at = 'test';
    $user->deleted_at = now();
    $user->deleted_at = null;
    $user->deleted_at = BaseCarbon::now();
}

function testInboundCast(User $user): void
{
    $user->secret = 'secret';
}

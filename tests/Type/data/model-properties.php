<?php

namespace ModelProperties;

use App\Account;
use App\Address;
use App\Casts\BackedEnumeration;
use App\Casts\BasicEnumeration;
use App\Group;
use App\GuardedModel;
use App\Role;
use App\RoleUser;
use App\Team;
use App\Thread;
use App\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

use function PHPStan\Testing\assertType;

function test(
    User $user,
    Account $account,
    Role $role,
    Group $group,
    Team $team,
    GuardedModel $guardedModel,
    Thread $thread,
    Address $address,
    RoleUser $roleUser,
    ModelWithCasts $modelWithCasts
): void {
    assertType('*ERROR*', $user->newStyleAttribute); // Doesn't have generic type, so we treat as it doesnt exist
    assertType('int', $user->stringButInt);
    assertType('string', $user->email);
    assertType('array', $user->allowed_ips);
    assertType('numeric-string', $user->floatButRoundedDecimalString);
    assertType('int<0, max>', $user->unsigned_integer);
    assertType('int<0, max>', $user->unsigned_integer_method);

    // Model Casts
    assertType('int', $user->int);
    assertType('int', $user->integer);
    assertType('float', $user->real);
    assertType('float', $user->float);
    assertType('float', $user->double);
    assertType('numeric-string', $user->decimal);
    assertType('string', $user->string);
    assertType('bool', $user->bool);
    assertType('bool', $user->boolean);
    assertType('stdClass', $user->object);
    assertType('array', $user->array);
    assertType('array', $user->json);
    assertType(Collection::class, $user->collection);
    assertType(Collection::class.'|null', $user->nullable_collection);
    assertType(Carbon::class, $user->date);
    assertType(Carbon::class, $user->datetime);
    assertType(CarbonImmutable::class, $user->immutable_date);
    assertType(CarbonImmutable::class, $user->immutable_datetime);
    assertType('int', $user->timestamp);
    assertType("'active'|'don\'t know'|'inactive'", $user->enum_status);
    assertType(BasicEnumeration::class, $user->basic_enum);
    assertType(BackedEnumeration::class, $user->backed_enum);

    // Castable
    assertType(Stringable::class, $user->castable_with_argument);

    // CastsAttributes
    assertType('App\ValueObjects\Favorites', $user->favorites);

    assertType('int', $user->id);
    assertType('bool', $user->blocked);
    assertType('Carbon\Carbon|null', $user->created_at);
    assertType('array', $user->meta);
    assertType('string', $account->active);
    assertType('string', $role->id);
    assertType('string', $team->id);
    assertType('int', $group->id);
    assertType('string', $guardedModel->name);
    assertType('string', $thread->custom_property);
    assertType('Illuminate\Database\Eloquent\Casts\ArrayObject', $user->options);
    assertType('int<0, max>', count($user->options));
    assertType('(Illuminate\Support\Collection<(int|string), mixed>|null)', $user->properties);
    assertType('int<0, max>', count($user->properties));
    assertType('mixed', $user->properties->first());
    assertType('non-falsy-string|null', $user->deleted_at?->format('d/m/Y'));
    assertType('int<0, max>', $address->user_id);
    assertType('int<0, max>', $address->custom_foreign_id_for_name);
    assertType('string', $address->address_id);
    assertType('string', $address->nullable_address_id); // overridden by a @property
    assertType('int<0, max>', $address->foreign_id_constrained);
    assertType('int<0, max>|null', $address->nullable_foreign_id_constrained);
    assertType('string', $address->foreign_uuid);
    assertType('string|null', $address->nullable_foreign_uuid);
    assertType('string', $address->foreign_ulid);
    assertType('string|null', $address->nullable_foreign_ulid);
    assertType('App\ValueObjects\Favorites', $user->favorites);
    assertType('string', $address->uuid);
    assertType('string', $roleUser->role_id);
    assertType('int<0, max>', $roleUser->user_id);

    assertType('bool', $modelWithCasts->integer);
    assertType(Stringable::class, $modelWithCasts->string);
}

class ModelWithCasts extends Model
{
    protected $casts = [
        'integer' => 'int',
    ];

    /**
     * @return array{integer: 'bool', string: 'Illuminate\\Database\\Eloquent\\Casts\\AsStringable:argument'}
     */
    public function casts(): array
    {
        $argument = 'argument';

        return [
            'integer' => 'bool', // overrides the cast from the property
            'string' => AsStringable::class.':'.$argument,
        ];
    }
}

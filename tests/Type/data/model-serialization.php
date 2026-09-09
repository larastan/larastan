<?php

namespace ModelSerialization;

use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

class SerializationModel extends Model
{
    protected $table = 'users';

    protected $visible = ['id', 'name', 'email_verified_at', 'label'];

    protected $appends = ['label'];

    protected $casts = ['email_verified_at' => 'datetime'];

    public function getLabelAttribute(): string
    {
        return 'label';
    }
}

function serialization(SerializationModel $model): void
{
    assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}', $model->attributesToArray());
    assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}', $model->toArray());
    assertType('mixed', $model->attributesToArray()['posts_count']);
}

class HiddenSerializationModel extends SerializationModel
{
    protected $hidden = ['name', 'label'];
}

class AccessorSerializationModel extends SerializationModel
{
    protected $casts = ['name' => 'integer'];

    public function getNameAttribute(): bool
    {
        return true;
    }
}

enum SerializedStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum SerializedNumber: int
{
    case One = 1;
    case Two = 2;
}

enum SerializedUnit
{
    case Active;
}

class CastSerializationModel extends Model
{
    protected $table = 'users';

    protected $visible = ['backed_enum', 'basic_enum', 'integer', 'date', 'datetime', 'collection'];

    protected $casts = [
        'backed_enum' => SerializedStatus::class,
        'basic_enum' => SerializedUnit::class,
        'integer' => SerializedNumber::class,
        'date' => 'immutable_date',
        'datetime' => 'datetime:Y-m-d',
        'collection' => 'collection',
    ];
}

class AppendedSerializationModel extends Model
{
    protected $table = 'no_serialization_schema';

    protected $appends = ['modern_date', 'legacy_date', 'tags', 'label'];

    /** @return \Illuminate\Database\Eloquent\Casts\Attribute<\Carbon\Carbon, never> */
    protected function modernDate(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(static fn () => new \Carbon\Carbon('2024-01-01'));
    }

    public function getLegacyDateAttribute(): \Carbon\Carbon
    {
        return new \Carbon\Carbon('2024-01-01');
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    public function getTagsAttribute(): \Illuminate\Support\Collection
    {
        return collect(['tag']);
    }

    public function getLabelAttribute(): ?string
    {
        return null;
    }
}

class OverriddenSerializationModel extends SerializationModel
{
    /** @return array{custom: true} */
    public function attributesToArray(): array
    {
        return ['custom' => true];
    }
}

class OverriddenToArrayModel extends SerializationModel
{
    /** @return array{custom: true} */
    public function toArray(): array
    {
        return ['custom' => true];
    }
}

class OverriddenDateModel extends SerializationModel
{
    /** @return array{year: int} */
    protected function serializeDate(\DateTimeInterface $date): array
    {
        return ['year' => (int) $date->format('Y')];
    }
}

function boundaries(
    HiddenSerializationModel $hidden,
    AccessorSerializationModel $accessor,
    CastSerializationModel $casts,
    AppendedSerializationModel $appended,
    OverriddenSerializationModel $override,
    OverriddenToArrayModel $toArrayOverride,
    OverriddenDateModel $dateOverride,
    Model $base,
): void {
    assertType('array{id?: int, email_verified_at?: string|null, ...<string, mixed>}', $hidden->attributesToArray());
    assertType('mixed', $hidden->attributesToArray()['name']);
    assertType('bool', $accessor->attributesToArray()['name']);
    assertType("'active'|'inactive'", $casts->attributesToArray()['backed_enum']);
    assertType("'Active'", $casts->attributesToArray()['basic_enum']);
    assertType('1|2', $casts->attributesToArray()['integer']);
    assertType('string', $casts->attributesToArray()['date']);
    assertType('string', $casts->attributesToArray()['datetime']);
    assertType('array<mixed>', $casts->attributesToArray()['collection']);
    assertType('array{modern_date?: string, legacy_date?: Carbon\\Carbon, tags?: array<int, string>, label?: string|null, ...<string, mixed>}', $appended->attributesToArray());
    assertType('array{custom: true}', $override->attributesToArray());
    assertType('array<mixed>', $override->toArray());
    assertType('array{custom: true}', $toArrayOverride->toArray());
    assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}', $toArrayOverride->attributesToArray());
    assertType('array{year: int}|null', $dateOverride->attributesToArray()['email_verified_at']);
    assertType('array<mixed>', $base->toArray());

    $partial = SerializationModel::query()->select('id')->firstOrFail();
    assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}', $partial->toArray());

    if (isset($partial->toArray()['name'])) {
        assertType('string', $partial->toArray()['name']);
    }
}

class ModernColumnSerializationModel extends SerializationModel
{
    protected $casts = ['name' => 'integer'];

    /** @return \Illuminate\Database\Eloquent\Casts\Attribute<string, never> */
    protected function name(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::get(static fn () => 'name');
    }

    public function serializeInside(): void
    {
        assertType('string', $this->attributesToArray()['name']);
        assertType('string', $this->toArray()['name']);
    }
}

class SerializationValue implements \Illuminate\Contracts\Support\Arrayable
{
    /** @return array{value: int} */
    public function toArray(): array
    {
        return ['value' => 1];
    }
}

/** @implements \Illuminate\Contracts\Database\Eloquent\CastsAttributes<SerializationValue, SerializationValue> */
class SerializationValueCast implements \Illuminate\Contracts\Database\Eloquent\CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): SerializationValue
    {
        return new SerializationValue();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return 'value';
    }

    /** @return array{serialized: true} */
    public function serialize(Model $model, string $key, mixed $value, array $attributes): array
    {
        return ['serialized' => true];
    }
}

class CustomCastSerializationModel extends SerializationModel
{
    protected $casts = ['name' => SerializationValueCast::class];
}

class ArrayableSerializationModel extends AppendedSerializationModel
{
    protected $appends = ['value', 'values'];

    public function getValueAttribute(): SerializationValue
    {
        return new SerializationValue();
    }

    /** @return \Illuminate\Support\Collection<string, SerializationValue|string> */
    public function getValuesAttribute(): \Illuminate\Support\Collection
    {
        return collect(['first' => new SerializationValue(), 'second' => 'value']);
    }
}

interface SerializationMarker
{
}

/** @template TModel of SerializationModel */
class GenericSerialization
{
    /** @param TModel $model */
    public function serialize(Model $model): void
    {
        assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}', $model->attributesToArray());
    }
}

function additionalBoundaries(
    CustomCastSerializationModel $custom,
    ArrayableSerializationModel $arrayable,
    SerializationModel&SerializationMarker $intersection,
    ModernColumnSerializationModel|AccessorSerializationModel $union,
    ?SerializationModel $nullable,
): void {
    assertType('array{serialized: true}', $custom->attributesToArray()['name']);
    assertType('array{value: int}', $arrayable->attributesToArray()['value']);
    assertType('array<string, array{value: int}|string>', $arrayable->attributesToArray()['values']);
    assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}', $intersection->attributesToArray());
    assertType('bool|string', $union->attributesToArray()['name']);
    assertType('array{id?: int, name?: string, email_verified_at?: string|null, label?: string, ...<string, mixed>}|null', $nullable?->attributesToArray());
}

class CustomCastAccessorSerializationModel extends CustomCastSerializationModel
{
    public function getNameAttribute(): string
    {
        return 'accessor';
    }
}

function customCastAccessor(CustomCastAccessorSerializationModel $model): void
{
    assertType('array{value: int}', $model->attributesToArray()['name']);
}

/** @implements \Illuminate\Contracts\Database\Eloquent\CastsAttributes<SerializedStatus, SerializedStatus> */
class SerializationEnumCast implements \Illuminate\Contracts\Database\Eloquent\CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): SerializedStatus
    {
        return SerializedStatus::Active;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return 'active';
    }
}

class CustomEnumSerializationModel extends SerializationModel
{
    protected $casts = ['name' => SerializationEnumCast::class];
}

function customEnumSerialization(CustomEnumSerializationModel $model): void
{
    assertType('ModelSerialization\\SerializedStatus', $model->attributesToArray()['name']);
}

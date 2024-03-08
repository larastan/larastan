<?php

namespace App;

use App\Casts\BackedEnumeration;
use App\Casts\BasicEnumeration;
use App\Casts\Favorites;
use App\Casts\Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

use function get_class;

/**
 * @property string $propertyDefinedOnlyInAnnotation
 *
 * @method Builder<static> scopeSomeScope(Builder $builder)
 *
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'meta' => 'array',
        'blocked' => 'boolean',
        'email_verified_at' => 'date',
        'allowed_ips' => 'encrypted:array',
        'floatButRoundedDecimalString' => 'decimal:1',
        'options' => AsArrayObject::class,
        'properties' => AsCollection::class,
        'castable_with_argument' => AsStringable::class.':argument',
        'favorites' => Favorites::class,
        'secret' => Hash::class.':sha256',
        'basic_enum' => BasicEnumeration::class,
        'backed_enum' => BackedEnumeration::class,

        'int' => 'int',
        'integer' => 'integer',
        'real' => 'real',
        'float' => 'float',
        'double' => 'double',
        'decimal' => 'decimal',
        'string' => 'string',
        'bool' => 'bool',
        'boolean' => 'boolean',
        'object' => 'object',
        'array' => 'array',
        'json' => 'json',
        'collection' => 'collection',
        'nullable_collection' => 'collection',
        'date' => 'date',
        'datetime' => 'datetime',
        'immutable_date' => 'immutable_date',
        'immutable_datetime' => 'immutable_datetime',
        'timestamp' => 'timestamp',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function id(): int
    {
        return $this->id;
    }

    public function getAllCapsName(): string
    {
        return Str::upper($this->name);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeWhereActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    /** @return BelongsTo<Group, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withTrashed();
    }

    /** @phpstan-return HasMany<Account> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function accounts_snake(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function accountsCamel(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /** @phpstan-return HasMany<ModelWithNonGenericCollection> */
    public function modelsWithNonGenericCollection(): HasMany
    {
        return $this->hasMany(ModelWithNonGenericCollection::class);
    }

    /** @phpstan-return HasMany<ModelWithOnlyValueGenericCollection> */
    public function modelsWithOnlyValueGenericCollection(): HasMany
    {
        return $this->hasMany(ModelWithOnlyValueGenericCollection::class);
    }

    public function transactions(): HasManyThrough
    {
        return $this->hasManyThrough(Transaction::class, Account::class);
    }

    public function syncableRelation(): HasManySyncable
    {
        return $this->hasManySyncable(Account::class);
    }

    public function address(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('some_column')
            ->wherePivotIn('some_column', [1, 2, 3]);
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(get_class($this));
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    public function hasManySyncable($related, $foreignKey = null, $localKey = null): HasManySyncable
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManySyncable(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }

    public function getOnlyAvailableWithAccessorAttribute(): string
    {
        return 'foo';
    }

    public function isActive(): bool
    {
        return $this->active === 1;
    }

    public function setActive(): void
    {
        $this->active = 1;
    }

    protected function newStyleAttribute(): Attribute
    {
        return Attribute::make(
            fn ($value) => 5,
        );
    }

    /**
     * @return Attribute<int, string>
     */
    protected function stringButInt(): Attribute
    {
        return Attribute::make(
            fn ($value) => 5,
            fn (string $value) => strtolower($value)
        );
    }

    /** This will not take any effect because it's public and does not specify generic types at return type. */
    public function email(): Attribute
    {
        return Attribute::make(
            fn ($value) => 5,
            fn (string $value) => strtolower($value)
        );
    }
}

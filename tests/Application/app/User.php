<?php

namespace App;

use App\Casts\Favorites;
use App\Casts\Hash;
use function get_class;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
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
use Tests\Application\HasManySyncable;

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
        'favorites' => Favorites::class,
        'secret' => Hash::class.':sha256',
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

    /** @phpstan-return BelongsTo<Group, User> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withTrashed();
    }

    /** @phpstan-return HasMany<Account> */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
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

    /**
     * @return Attribute<int, never>
     */
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

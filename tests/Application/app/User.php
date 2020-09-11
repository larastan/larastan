<?php

namespace App;

use function get_class;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tests\Application\HasManySyncable;

/**
 * @property-read \App\AccountCollection $accounts
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;

    /** @var array<int, model-property<self>|'*'> */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /** @var array<model-property<self>, string> */
    protected $casts = ['meta' => 'array', 'blocked' => 'boolean'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<model-property<self>>
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

    /**
     * @param model-property<self> $property
     */
    public function printPropertySelf(string $property): void
    {
        echo $this->{$property};
    }

    /**
     * @param model-property<static> $property
     */
    public function printPropertyStatic(string $property): void
    {
        echo $this->{$property};
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

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

    public function addressable(): MorphTo
    {
        return $this->morphTo(null, 'model_type', 'model_id');
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

    /**
     * @param string $related
     * @param string|null $foreignKey
     * @param string|null $localKey
     * @return HasManySyncable
     */
    public function hasManySyncable($related, $foreignKey = null, $localKey = null): HasManySyncable
    {
        $instance = $this->newRelatedInstance($related);

        $foreignKey = $foreignKey ?: $this->getForeignKey();

        $localKey = $localKey ?: $this->getKeyName();

        return new HasManySyncable(
            $instance->newQuery(), $this, $instance->getTable().'.'.$foreignKey, $localKey
        );
    }
}

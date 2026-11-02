<?php

declare(strict_types=1);

namespace ModelRelationDefaults;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ValidDefaults extends User
{
    protected $with = ['accounts.transactions:id', 'group:id'];
    protected $withCount = ['accounts AS total'];
}

class InvalidDefaults extends User
{
    protected $with = ['accounts.transactions', 'missing', 'accounts.missing'];
    protected $withCount = ['missing as total', 'accounts  as  total', 'accounts.transactions'];
}

abstract class ParentDefaults extends Model
{
    protected $with = ['children'];
    protected $withCount = ['children as total'];
}

class ValidChild extends ParentDefaults
{
    /** @return HasMany<User, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

class InvalidChild extends ParentDefaults
{
}

class OverriddenDefaults extends ParentDefaults
{
    protected $with = [];
    protected $withCount = [];
}

trait DefaultsFromTrait
{
    protected $with = ['accounts:id' => ['transactions', 'missing']];
}

class TraitDefaults extends User
{
    use DefaultsFromTrait;
}

class ConstantDefaults extends User
{
    private const RELATIONS = [2 => 'missing'];
    protected $with = self::RELATIONS;
}

class NonModel
{
    protected $with = ['missing'];
    protected $withCount = ['missing'];
}

class UnknownRelatedDefault extends \App\Comment
{
    protected $with = ['commentable.anything'];
}

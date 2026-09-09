<?php

namespace NoImplicitQueryBuilderCallScopes;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /** @param Builder<static> $query */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('active', true);
    }

    /** @param Builder<static> $query */
    #[Scope]
    public function named(Builder $query, string $name): void
    {
        $query->where('name', $name);
    }

    /** @param Builder<static> $query */
    #[Scope]
    private function recent(Builder $query): void
    {
        $query->latest();
    }

    /** @param Builder<static> $query */
    public function compose(Builder $query): void
    {
        $this->active($query);
        $this->recent($query);
        self::active($query);
        static::active($query);
        $this->named($query, 'Jane');
    }
}

class ChildUser extends User
{
    /** @param Builder<static> $query */
    public function composeInherited(Builder $query): void
    {
        $this->active($query);
        parent::active($query);
    }
}

/** @param Builder<User> $query */
function scopes(User $user, ChildUser $child, Builder $query): void
{
    User::active();
    $user->active();
    ChildUser::active();
    $child->active();
    User::query()->active();
    $user->newQuery()->active();
    $user->named($query, 'Jane');
}

<?php

namespace BuilderOfType;

use App\Account;
use App\ChildTeamBuilder;
use App\Team;
use App\User;
use Illuminate\Database\Eloquent\Builder;

class BuilderOfTypeTest
{
    /**
     * @phpstan-param builder-of<User> $userQuery
     */
    public function acceptsUserBuilder(Builder $userQuery): void
    {
    }

    /**
     * @phpstan-param builder-of<Account> $accountQuery
     */
    public function acceptsAccountBuilder(Builder $accountQuery): void
    {
    }

    /**
     * @phpstan-param builder-of<Team> $teamQuery
     */
    public function acceptsTeamBuilder(ChildTeamBuilder $teamQuery): void
    {
    }

    public function testValidUsage(): void
    {
        $this->acceptsUserBuilder(User::query());
        $this->acceptsAccountBuilder(Account::query());
        $this->acceptsTeamBuilder(Team::query());
    }

    public function testInvalidUsage(): void
    {
        // passing a User builder to a method expecting Account builder
        $this->acceptsAccountBuilder(User::query());

        // passing an Account builder to the method expecting a User builder
        $this->acceptsUserBuilder(Account::query());

        // passing a Team builder to the method expecting a User builder
        $this->acceptsUserBuilder(Team::query());
    }
}

class GenericTest {
    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param class-string<TModel> $modelClass
     *
     * @return builder-of<TModel>
     */
    function getQueryBuilder(string $modelClass): Builder
    {
        return $modelClass::query();
    }

    /**
     * @param Builder<Account> $builder
     */
    function acceptsAccountBuilder(Builder $builder): void
    {

    }

    /**
     * @param ChildTeamBuilder $builder
     */
    function acceptsTeamBuilder(ChildTeamBuilder $builder): void
    {

    }
}

function testGenericBuilderHandler(GenericTest $generic): void
{
    $userBuilder = $generic->getQueryBuilder(User::class);
    $accountBuilder = $generic->getQueryBuilder(Account::class);
    $teamBuilder = $generic->getQueryBuilder(Team::class);

    $generic->acceptsAccountBuilder($userBuilder);
    $generic->acceptsAccountBuilder($accountBuilder);
    $generic->acceptsTeamBuilder($teamBuilder);
}
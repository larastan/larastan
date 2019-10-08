<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BuilderExtension
{
    /** @return Collection|User[] */
    public function testCallingGetOnModelWithStaticQueryBuilder() : Collection
    {
        return User::where('id', 1)->get();
    }

    /** @return Collection|User[] */
    public function testCallingGetOnModelWithVariableQueryBuilder() : Collection
    {
        return (new User)->where('id', 1)->get();
    }

    /** @return Collection|User[] */
    public function testCallingLongGetChainOnModelWithStaticQueryBuilder() : Collection
    {
        return User::where('id', 1)
            ->whereNotNull('active')
            ->whereHas(['relation'])
            ->whereFoo(['bar'])
            ->get();
    }


    /** @return Collection|User[] */
    public function testCallingLongGetChainOnModelWithVariableQueryBuilder() : Collection
    {
        return (new User)->whereNotNull('active')
            ->whereHas(['relation'])
            ->whereFoo(['bar'])
            ->get();
    }

    /** @return Collection|User[] */
    public function testCallingGetOnModelWithVariableQueryBuilder2() : Collection
    {
        $user = new User;

        return $user->where('test', 1)->get();
    }
}

class TestModel extends Model
{
    /** @return Collection|TestModel[] */
    public function testCallingGetInsideModel() : Collection
    {
        return $this->where('test', 1)->get();
    }
}

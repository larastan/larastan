<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\Group;
use App\User;

class CorrectModelInstantiation
{
    use ModelMakeTrait;

    public function construct(): User
    {
        return new User();
    }

    public function create(): User
    {
        return User::create();
    }

    public function relationMake(): Group
    {
        return (new User)->group()->make();
    }

    public function makeFromTrait(): User
    {
        return $this->makeInTrait();
    }

    protected function makeInTrait(): User
    {
        return new User;
    }
}

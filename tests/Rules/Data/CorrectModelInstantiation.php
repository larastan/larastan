<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\Group;
use App\User;

class CorrectModelInstantiation
{
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
}

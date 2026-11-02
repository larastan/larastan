<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\User;

trait ModelMakeTrait
{
    protected function makeInTrait(): User
    {
        return User::make();
    }
}

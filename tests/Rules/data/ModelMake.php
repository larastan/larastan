<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use App\User;

class ModelMake
{
    use ModelMakeTrait;

    public function make(): User
    {
        return User::make();
    }

    public function makeStringClass(): User
    {
        $class = User::class;

        return $class::make();
    }

    public function makeFromTrait(): User
    {
        return $this->makeInTrait();
    }
}

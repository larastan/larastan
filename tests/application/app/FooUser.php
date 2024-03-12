<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;


class FooUser extends Authenticatable
{
    protected $connection = 'foo';

    public function getTable()
    {
        return 'users';
    }
}

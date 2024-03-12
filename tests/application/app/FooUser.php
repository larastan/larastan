<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;


class FooUser extends Authenticatable
{
    // test overriding methods
    public function getConnectionName()
    {
        return 'foo';
    }

    public function getTable()
    {
        return 'users';
    }
}

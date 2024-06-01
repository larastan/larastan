<?php

namespace ModelPropertyStaticCall;

use App\User;
use Illuminate\Database\Eloquent\Model;

function foo(User $foo): User
{
    User::create([
        'foo' => 'bar',
    ]);

    return $foo::create([
        'foo' => 'bar',
    ]);
}

/**
 * @property string $name
 */
class ModelPropertyStaticCallsInClass extends Model
{
    public static function foo(): ModelPropertyStaticCallsInClass
    {
        return static::create([
            'foo' => 'bar',
            'name' => 'John Doe',
        ]);
    }

    public function bar(): ModelPropertyStaticCallsInClass
    {
        return self::create([
            'foo' => 'bar',
            'name' => 'John Doe',
        ]);
    }
}

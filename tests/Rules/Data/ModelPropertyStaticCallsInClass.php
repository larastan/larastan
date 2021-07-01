<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Database\Eloquent\Model;

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

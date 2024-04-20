<?php

namespace ModelPropertiesL11;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use function PHPStan\Testing\assertType;

function test(ModelWithCasts $modelWithCasts): void
{
    assertType('bool', $modelWithCasts->integer);
    assertType(Stringable::class, $modelWithCasts->string);
}

class ModelWithCasts extends Model
{
    protected $casts = [
        'integer' => 'int',
    ];

    /**
     * @return array{integer: 'bool', string: 'Illuminate\\Database\\Eloquent\\Casts\\AsStringable:argument'}
     */
    public function casts(): array
    {
        $argument = 'argument';

        return [
            'integer' => 'bool', // overrides the cast from the property
            'string' => AsStringable::class.':'.$argument,
        ];
    }
}

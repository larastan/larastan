<?php

namespace Bug2554;

use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Foo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Foo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Foo query()
 */
class Foo extends Model
{
    public function ownQuery(): void
    {
        assertType('Illuminate\Database\Eloquent\Builder<static(Bug2554\Foo)>', $this->newQuery());
    }
}

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FinalFoo query()
 */
final class FinalFoo extends Model
{
}

function test(Foo $foo): void
{
    assertType('Illuminate\Database\Eloquent\Builder<Bug2554\Foo>', Foo::query());
    assertType('Illuminate\Database\Eloquent\Builder<Bug2554\Foo>', $foo->newQuery());
    assertType('Illuminate\Database\Eloquent\Builder<Bug2554\Foo>', $foo->newModelQuery());
    assertType('Illuminate\Database\Eloquent\Builder<Bug2554\FinalFoo>', FinalFoo::query());
}

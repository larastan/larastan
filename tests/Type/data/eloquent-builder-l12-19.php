<?php

declare(strict_types=1);

namespace EloquentBuilder1215;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use function PHPStan\Testing\assertType;

#[UseEloquentBuilder(CustomBuilder::class)]
class A extends \Illuminate\Database\Eloquent\Model {}

#[UseEloquentBuilder(CustomBuilder::class)]
class B extends \Illuminate\Database\Eloquent\Model
{
    public function newEloquentBuilder($query): AnotherCustomBuilder
    {
        return new AnotherCustomBuilder($query);
    }
}

#[UseEloquentBuilder('EloquentBuilder1215\CustomBuilder')]
class C extends \Illuminate\Database\Eloquent\Model {}

#[UseEloquentBuilder('\EloquentBuilder1215\CustomBuilder')]
class D extends \Illuminate\Database\Eloquent\Model {}

#[UseEloquentBuilder(builderClass: CustomBuilder::class)]
class E extends \Illuminate\Database\Eloquent\Model {}

#[UseEloquentBuilder('EloquentBuilder1215\MissingBuilder')]
class F extends \Illuminate\Database\Eloquent\Model {}

/** @extends Builder<A> */
class CustomBuilder extends \Illuminate\Database\Eloquent\Builder {}

/** @extends Builder<B> */
class AnotherCustomBuilder extends \Illuminate\Database\Eloquent\Builder {}

function test(): void
{
    assertType('EloquentBuilder1215\CustomBuilder', A::query());
    assertType('EloquentBuilder1215\AnotherCustomBuilder', B::query());
    assertType('EloquentBuilder1215\CustomBuilder', C::query());
    assertType('EloquentBuilder1215\CustomBuilder', D::query());
    assertType('EloquentBuilder1215\CustomBuilder', E::query());
    assertType('Illuminate\Database\Eloquent\Builder<EloquentBuilder1215\F>', F::query());
}

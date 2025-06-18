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

/** @extends Builder<A> */
class CustomBuilder extends \Illuminate\Database\Eloquent\Builder {}

/** @extends Builder<B> */
class AnotherCustomBuilder extends \Illuminate\Database\Eloquent\Builder {}

function test(): void
{
    assertType('EloquentBuilder1215\CustomBuilder<EloquentBuilder1215\A>', A::query());
    assertType('EloquentBuilder1215\AnotherCustomBuilder<EloquentBuilder1215\B>', B::query());
}

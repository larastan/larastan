<?php

namespace CollectionHelper;

use function PHPStan\Testing\assertType;

/**
 * @template T
 */
class Ok
{
    /**
     * @param  T  $value
     */
    public function __construct(public mixed $value)
    {
    }
}

/**
 * @template T
 */
class Some
{
    /**
     * @param  T  $value
     */
    public function __construct(public mixed $value)
    {
    }
}

assertType('Illuminate\Support\Collection<int, CollectionHelper\Ok<CollectionHelper\Some<int>>>', collect([new Ok(new Some(42))]));
assertType('Illuminate\Support\Collection<int, CollectionHelper\Ok<CollectionHelper\Some<int>>>', collect([42])->map(fn (int $i) => new Ok(new Some($i))));

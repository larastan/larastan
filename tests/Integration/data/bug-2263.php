<?php

namespace Bug2263;

/**
 * @template T of \Illuminate\Database\Eloquent\Model
 *
 * @param  class-string<T>  $class
 * @return T
 */
function test(string $class): mixed
{
    return $class::findOrFail(1);
}

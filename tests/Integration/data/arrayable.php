<?php

namespace ArrayableIntegration;

use Illuminate\Contracts\Support\Arrayable;

/** @param Arrayable<string, int> $value */
function integers(Arrayable $value): void
{
}

/** @param Arrayable<array<string, string>> $value */
function incompatible(Arrayable $value): void
{
    integers($value);
}

/** @param Arrayable<\stdClass, int> $value */
function invalidKeys(Arrayable $value): void
{
}

/** @param Arrayable<string, contravariant int> $value */
function invalidVariance(Arrayable $value): void
{
}

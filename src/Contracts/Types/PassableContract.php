<?php

declare(strict_types=1);

namespace Larastan\Larastan\Contracts\Types;

use PHPStan\Type\Type;

/** @internal */
interface PassableContract
{
    public function getType(): Type;

    public function setType(Type $type): void;
}

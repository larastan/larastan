<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Larastan\Larastan\Contracts\Types\PassableContract;
use PHPStan\Type\Type;

/** @internal */
final class Passable implements PassableContract
{
    public function __construct(private Type $type)
    {
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function setType(Type $type): void
    {
        $this->type = $type;
    }
}

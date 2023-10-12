<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use NunoMaduro\Larastan\Contracts\Types\PassableContract;
use PHPStan\Type\Type;

/**
 * @internal
 */
final class Passable implements PassableContract
{
    /**
     * @var \PHPStan\Type\Type
     */
    private $type;

    /**
     * Passable constructor.
     */
    public function __construct(Type $type)
    {
        $this->type = $type;
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

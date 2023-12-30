<?php

namespace Larastan\Larastan\Contracts\Types;

use PHPStan\Type\Type;

/**
 * @internal
 */
interface PassableContract
{
    /**
     * @return \PHPStan\Type\Type
     */
    public function getType(): Type;

    /**
     * @param  \PHPStan\Type\Type  $type
     * @return void
     */
    public function setType(Type $type): void;
}

<?php
/*
 * This file is part of PhpStorm.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Contracts\Types;

use PHPStan\Type\Type;

/**
 * @internal
 */
interface PassableContract
{
    public function getType(): Type;

    public function setType(Type $type): void;
}

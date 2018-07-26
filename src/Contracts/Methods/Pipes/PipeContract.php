<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Contracts\Methods\Pipes;

use Closure;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;

/**
 * @internal
 */
interface PipeContract
{
    /**
     * @param \NunoMaduro\Larastan\Contracts\Methods\PassableContract $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(PassableContract $passable, Closure $next): void;
}

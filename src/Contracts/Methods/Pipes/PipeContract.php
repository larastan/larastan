<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Contracts\Methods\Pipes;

use Closure;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;

/**
 * @internal
 */
interface PipeContract
{
    public function handle(PassableContract $passable, Closure $next): void;
}

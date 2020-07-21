<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Contracts\Types\Pipes;

use Closure;
use NunoMaduro\Larastan\Contracts\Types\PassableContract;

/**
 * @internal
 */
interface PipeContract
{
    public function handle(PassableContract $passable, Closure $next): void;
}

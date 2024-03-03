<?php

declare(strict_types=1);

namespace Larastan\Larastan\Contracts\Types\Pipes;

use Closure;
use Larastan\Larastan\Contracts\Types\PassableContract;

/** @internal */
interface PipeContract
{
    public function handle(PassableContract $passable, Closure $next): void;
}

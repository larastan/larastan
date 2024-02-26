<?php

declare(strict_types=1);

namespace Larastan\Larastan\Contracts\Methods\Pipes;

use Closure;
use Larastan\Larastan\Contracts\Methods\PassableContract;

/** @internal */
interface PipeContract
{
    public function handle(PassableContract $passable, Closure $next): void;
}

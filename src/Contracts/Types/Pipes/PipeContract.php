<?php

declare(strict_types=1);

namespace Larastan\Larastan\Contracts\Types\Pipes;

use Closure;
use Larastan\Larastan\Contracts\Types\PassableContract;

/**
 * @internal
 */
interface PipeContract
{
    /**
     * @param  \Larastan\Larastan\Contracts\Types\PassableContract  $passable
     * @param  \Closure  $next
     * @return void
     */
    public function handle(PassableContract $passable, Closure $next): void;
}

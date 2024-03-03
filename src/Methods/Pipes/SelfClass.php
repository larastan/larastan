<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods\Pipes;

use Closure;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Contracts\Methods\Pipes\PipeContract;

/** @internal */
final class SelfClass implements PipeContract
{
    public function handle(PassableContract $passable, Closure $next): void
    {
        $className = $passable->getClassReflection()
            ->getName();

        if ($passable->searchOn($className)) {
            return;
        }

        $next($passable);
    }
}

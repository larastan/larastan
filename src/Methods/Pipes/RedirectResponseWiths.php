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

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use function substr;
use Illuminate\Http\RedirectResponse;
use NunoMaduro\Larastan\Methods\Passable;

/**
 * @internal
 */
final class RedirectResponseWiths
{
    /**
     * @param \NunoMaduro\Larastan\Methods\Passable $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(Passable $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();
        $methodName = $passable->getMethodName();

        $found = false;

        $instanceOfRedirectResponse = $classReflection->getName() === RedirectResponse::class;

        if ($instanceOfRedirectResponse && strlen($methodName) > 4 && substr(
                $methodName,
                0,
                4
            ) === 'with' && $classReflection->hasNativeMethod('with')) {
            $passable->setMethodReflection($methodReflection = $classReflection->getNativeMethod('with'));
            $found = true;
        }

        if (! $found) {
            $next($passable);
        }
    }
}

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

namespace NunoMaduro\Larastan\Middlewares;

use Closure;
use PHPStan\Broker\Broker;
use NunoMaduro\Larastan\Passable;
use PHPStan\Reflection\ClassReflection;

/**
 * @internal
 */
final class Mixins
{
    /**
     * @param \NunoMaduro\Larastan\Passable $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(Passable $passable, Closure $next): void
    {
        $mixins = $this->getMixinsFromClass($passable->getBroker(), $passable->getClassReflection());

        $found = false;

        foreach ($mixins as $mixin) {
            if ($found = $passable->inception($mixin)) {
                break;
            }
        }

        if (! $found) {
            $next($passable);
        }
    }

    /**
     * @param \PHPStan\Broker\Broker $broker
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @return array
     */
    private function getMixinsFromClass(Broker $broker, ClassReflection $classReflection): array
    {
        preg_match_all(
            '/{@mixin\s+([\w\\\\]+)/',
            (string) $classReflection->getNativeReflection()
                ->getDocComment(),
            $mixins
        );

        $mixins = array_map(
            function ($mixin) {
                return preg_replace('#^\\\\#', '', $mixin);
            },
            $mixins[1]
        );

        preg_match_all(
            '/{@see\s+([\w\\\\]+)/',
            (string) $classReflection->getNativeReflection()
                ->getDocComment(),
            $sees
        );

        $sees = array_map(
            function ($see) {
                return preg_replace('#^\\\\#', '', $see);
            },
            $sees[1]
        );

        $mixins = array_merge($mixins, $sees);

        if (! empty($mixins)) {
            foreach ($mixins as $mixin) {
                $mixins = array_merge($mixins, $this->getMixinsFromClass($broker, $broker->getClass($mixin)));
            }
        }

        return $mixins;
    }
}

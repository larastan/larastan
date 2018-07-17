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
use NunoMaduro\Larastan\Concerns\HasContainer;

/**
 * @internal
 */
final class Mixins
{
    use HasContainer;

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
            if ($found = $passable->sendToPipeline($mixin)) {
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
    public function getMixinsFromClass(Broker $broker, ClassReflection $classReflection): array
    {
        $phpdocs = (string) $classReflection->getNativeReflection()
            ->getDocComment();

        $mixins = array_merge(
            $this->getMixinsFromPhpDocs($phpdocs, '/@mixin\s+([\w\\\\]+)/'),
            $this->getMixinsFromPhpDocs($phpdocs, '/@see\s+([\w\\\\]+)/'),
            $classReflection->getParentClassesNames(),
            $this->resolve('config')
                ->get('larastan.mixins')[$classReflection->getName()] ?? []
        );

        if (! empty($mixins)) {
            foreach ($mixins as $mixin) {
                $mixins = array_merge($mixins, $this->getMixinsFromClass($broker, $broker->getClass($mixin)));
            }
        }

        return array_unique($mixins);
    }

    /**
     * @param  string $phpdocs
     * @param  string $pattern
     *
     * @return array
     */
    private function getMixinsFromPhpDocs(string $phpdocs, string $pattern): array
    {
        preg_match_all(
            $pattern,
            (string) $phpdocs,
            $mixins
        );

        return array_map(
            function ($mixin) {
                return preg_replace('#^\\\\#', '', $mixin);
            },
            $mixins[1]
        );
    }
}

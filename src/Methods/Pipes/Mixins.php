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
use PHPStan\Broker\Broker;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class Mixins implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * Already resolved mixins.
     *
     * @var array
     */
    private static $resolved = [];

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
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
        $phpdocs = (string) $classReflection->getNativeReflection()->getDocComment();

        $mixins = array_merge(
            $this->getMixinsFromPhpDocs($phpdocs, '/@mixin\s+([\w\\\\]+)/'),
            $this->getMixinsFromPhpDocs($phpdocs, '/@see\s+([\w\\\\]+)/'),
            $classReflection->getParentClassesNames(),
            $this->resolve('config')
                ->get('larastan.mixins')[$classReflection->getName()] ?? []
        );

        $mixins = array_filter($mixins, function ($mixin) use ($classReflection) {
            try {
                return (new \ReflectionClass($mixin))->getName() !== $classReflection->getName();
            } catch (\ReflectionException $e) {
                return false;
            }
        });

        if (! empty($mixins)) {
            foreach ($mixins as $mixin) {
                if (! array_key_exists($mixin, self::$resolved)) {
                    /*
                     * Marks as resolved.
                     */
                    self::$resolved[$mixin] = [];

                    self::$resolved[$mixin] = $this->getMixinsFromClass($broker, $broker->getClass($mixin));
                }
                $mixins = array_merge($mixins, self::$resolved[$mixin]);
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
        preg_match_all($pattern, $phpdocs, $mixins);

        return array_map(function ($mixin) {
            return preg_replace('#^\\\\#', '', $mixin);
        }, $mixins[1]);
    }
}

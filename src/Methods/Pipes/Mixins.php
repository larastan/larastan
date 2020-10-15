<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @internal
 */
final class Mixins implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * Already resolved mixins.
     *
     * @var array<array<string>>
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
     * @param ReflectionProvider $reflectionProvider
     * @param ClassReflection    $classReflection
     *
     * @return string[]
     * @throws ClassNotFoundException
     */
    public function getMixinsFromClass(ReflectionProvider $reflectionProvider, ClassReflection $classReflection): array
    {
        $phpdocs = (string) $classReflection->getNativeReflection()->getDocComment();

        $mixins = array_merge(
            $this->getMixinsFromPhpDocs($phpdocs, '/@mixin\s+([\w\\\\]+)/'),
            $this->getMixinsFromPhpDocs($phpdocs, '/@see\s+([\w\\\\]+)/'),
            $classReflection->getParentClassesNames(),
            $this->resolve('config') === null ?
                [] :
                $this->resolve('config')->get('larastan.mixins')[$classReflection->getName()] ?? []
        );

        $mixins = array_filter($mixins, static function ($mixin) use ($classReflection) {
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

                    self::$resolved[$mixin] = $this->getMixinsFromClass($reflectionProvider, $reflectionProvider->getClass($mixin));
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
     * @return string[]
     */
    private function getMixinsFromPhpDocs(string $phpdocs, string $pattern): array
    {
        preg_match_all($pattern, $phpdocs, $mixins);

        return array_map(static function ($mixin) {
            return preg_replace('#^\\\\#', '', $mixin);
        }, $mixins[1]);
    }
}

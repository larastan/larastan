<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class Facades implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Facade::class)) {
            $facadeClass = $classReflection->getName();

            if ($concrete = $facadeClass::getFacadeRoot()) {
                $found = $passable->sendToPipeline(get_class($concrete), true);
            }

            if (! $found && Str::startsWith($passable->getMethodName(), 'assert')) {
                $found = $passable->sendToPipeline($this->getFake($classReflection->getName()), true);
            }
        }

        if (! $found) {
            $next($passable);
        }
    }

    private function getFake(string $facade): string
    {
        $shortClassName = substr($facade, strrpos($facade, '\\') + 1);

        return sprintf("\Illuminate\Support\Testing\Fakes\%sFake", $shortClassName);
    }
}

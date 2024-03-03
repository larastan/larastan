<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Contracts\Methods\Pipes\PipeContract;
use PHPStan\Reflection\ClassReflection;

/** @internal */
final class Contracts implements PipeContract
{
    use Concerns\HasContainer;

    public function handle(PassableContract $passable, Closure $next): void
    {
        $found = false;

        foreach ($this->concretes($passable->getClassReflection()) as $concrete) {
            if ($passable->sendToPipeline($concrete)) {
                $found = true;

                break;
            }
        }

        if ($found) {
            return;
        }

        $next($passable);
    }

    /** @return class-string[] */
    private function concretes(ClassReflection $classReflection): array
    {
        if ($classReflection->isInterface() && Str::startsWith($classReflection->getName(), 'Illuminate\Contracts')) {
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $class = $concrete::class;

                if ($class) {
                    return [$class];
                }
            }
        }

        return [];
    }
}

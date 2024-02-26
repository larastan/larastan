<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Contracts\Methods\Pipes\PipeContract;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Throwable;

use function assert;
use function class_exists;
use function sprintf;
use function strrpos;
use function substr;

/** @internal */
final class Facades implements PipeContract
{
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $found = false;

        if ($classReflection->isSubclassOf(Facade::class)) {
            $facadeClass = $classReflection->getName();

            if (ReflectionHelper::hasMethodTag($classReflection, $passable->getMethodName())) {
                $next($passable);

                return;
            }

            $concrete = null;

            try {
                $concrete = $facadeClass::getFacadeRoot();
            } catch (Throwable) {
            }

            if ($concrete) {
                $class = $concrete::class;

                if ($class) {
                    $found = $passable->sendToPipeline($class, true);
                }
            }

            if (! $found && Str::startsWith($passable->getMethodName(), 'assert')) {
                $fakeFacadeClass = $this->getFake($facadeClass);

                if ($passable->getReflectionProvider()->hasClass($fakeFacadeClass)) {
                    assert(class_exists($fakeFacadeClass));
                    $found = $passable->sendToPipeline($fakeFacadeClass, true);
                }
            }
        }

        if ($found) {
            return;
        }

        $next($passable);
    }

    private function getFake(string $facade): string
    {
        $shortClassName = substr($facade, strrpos($facade, '\\') + 1);

        return sprintf('\\Illuminate\\Support\\Testing\\Fakes\\%sFake', $shortClassName);
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Extensions\Methods\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;
use NunoMaduro\Larastan\Helpers\BuilderHelper;
use PHPStan\Type\ObjectType;

/**
 * @internal
 */
final class BuilderDynamicWheres implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();
        $found = false;
        $builderHelper = new BuilderHelper($passable->getBroker());

        if ($classReflection->getName() === Builder::class || $classReflection->isSubclassOf(Builder::class)) {
            if ($returnMethodReflection = $builderHelper->dynamicWhere($passable->getMethodName(), new ObjectType(EloquentBuilder::class))) {
                $passable->setMethodReflection($returnMethodReflection);

                $found = true;
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

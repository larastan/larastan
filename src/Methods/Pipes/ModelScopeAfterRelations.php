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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;
use NunoMaduro\Larastan\Reflection\ModelScopeMethodReflection;

final class ModelScopeAfterRelations implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();
        $builderClass = $passable->getBroker()->getClass(Builder::class);
        $isRelationSubclass = $classReflection->isSubclassOf(Relation::class);

        $found = false;

        if ($isRelationSubclass &&
            ! $classReflection->isAbstract() &&
            ! $builderClass->hasNativeMethod($passable->getMethodName())
        ) {
            $passable->setMethodReflection(
                new ModelScopeMethodReflection(
                    $passable->getMethodName(),
                    $passable->getBroker()->getClass(Model::class),
                    $classReflection
                )
            );

            $found = true;
        }

        if (! $found) {
            $next($passable);
        }
    }
}

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

namespace NunoMaduro\Larastan\Methods;

use PHPStan\Broker\Broker;
use Illuminate\Pipeline\Pipeline;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;

/**
 * @internal
 */
final class Kernel
{
    use Concerns\HasContainer;

    /**
     * @param \PHPStan\Broker\Broker $broker
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @param string $methodName
     *
     * @return \NunoMaduro\Larastan\Contracts\Methods\PassableContract
     */
    public function handle(Broker $broker, ClassReflection $classReflection, string $methodName): PassableContract
    {
        $pipeline = new Pipeline($this->getContainer());

        $passable = new Passable($broker, $pipeline, $classReflection, $methodName);

        $pipeline->send($passable)
            ->through(
                [
                    Pipes\SelfClass::class,
                    Pipes\Macros::class,
                    Pipes\Mixins::class,
                    Pipes\Contracts::class,
                    Pipes\Facades::class,
                    Pipes\Managers::class,
                    Pipes\Auths::class,
                    Pipes\ModelScopes::class,
                    Pipes\BuilderLocalMacros::class,
                    Pipes\BuilderDynamicWheres::class,
                    Pipes\RedirectResponseWiths::class,
                ]
            )
            ->then(
                function ($method) {
                }
            );

        return $passable;
    }
}

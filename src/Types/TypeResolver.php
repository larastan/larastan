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

namespace NunoMaduro\Larastan\Types;

use PHPStan\Type\Type;
use Illuminate\Pipeline\Pipeline;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Types\Pipes\ObjectType;

/**
 * @internal
 */
final class TypeResolver
{
    use Concerns\HasContainer;

    /**
     * @param \PHPStan\Type\Type $type
     * @return \PHPStan\Type\Type
     */
    public function resolveFrom(Type $type): Type
    {
        $pipeline = new Pipeline($this->getContainer());

        $passable = new Passable($type);

        $pipeline->send($passable)
            ->through(
                [
                    ObjectType::class,
                ]
            )
            ->then(
                function ($passable) {
                }
            );

        return $passable->getType();
    }
}

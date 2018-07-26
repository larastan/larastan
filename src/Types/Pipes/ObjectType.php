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

namespace NunoMaduro\Larastan\Types\Pipes;

use Closure;
use PHPStan\Type\MixedType;
use PHPStan\Type\UnionType;
use PHPStan\Type\ObjectWithoutClassType;
use NunoMaduro\Larastan\Contracts\Types\PassableContract;
use NunoMaduro\Larastan\Contracts\Types\Pipes\PipeContract;

/**
 * @internal
 */
final class ObjectType implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $type = $passable->getType();

        if ($type instanceof UnionType) {
            $types = $type->getTypes();
            foreach ($types as $key => $type) {
                if ($type instanceof ObjectWithoutClassType) {
                    $types[$key] = new MixedType();
                }
            }

            $passable->setType(new UnionType($types));
        }

        if ($type instanceof ObjectWithoutClassType) {
            $passable->setType(new MixedType());
        }

        $next($passable);
    }
}

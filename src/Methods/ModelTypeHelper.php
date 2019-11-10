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

use Illuminate\Database\Eloquent\Model;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticResolvableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class ModelTypeHelper
{
    public static function replaceStaticTypeWithModel(Type $type, string $modelClass) : Type
    {
        if ($type instanceof UnionType) {
            $types = $type->getTypes();
            foreach ($types as $key => $innerType) {
                if ($innerType instanceof ObjectWithoutClassType) {
                    $types[$key] = new ObjectType($modelClass);
                }
            }

            $type = new UnionType($types);
        }

        if ($type instanceof StaticResolvableType) {
            return TypeCombinator::remove($type->resolveStatic($modelClass), new ObjectType(Model::class, new ObjectType($modelClass)));
        }

        return $type;
    }
}

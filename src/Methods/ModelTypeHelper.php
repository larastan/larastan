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
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IterableType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

final class ModelTypeHelper
{
    public static function replaceStaticTypeWithModel(Type $type, string $modelClass) : Type
    {
        $type = TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($modelClass): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if ($type instanceof IterableType) {
                return $traverse($type->getItemType());
            }

            if ($type instanceof ObjectWithoutClassType || $type instanceof StaticType) {
                return new GenericObjectType(Model::class, [new ObjectType($modelClass)]);
            }

            return $type;
        });

        return TypeCombinator::remove($type, new ObjectType(Model::class, new ObjectType($modelClass)));
    }
}

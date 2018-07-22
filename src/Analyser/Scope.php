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

namespace NunoMaduro\Larastan\Analyser;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use ReflectionClass;
use function gettype;
use PHPStan\Type\Type;
use function get_class;
use function is_object;
use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\UnionType;
use PHPStan\Type\TypehintHelper;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Analyser\Scope as BaseScope;
use PHPStan\Type\ObjectWithoutClassType;
use Illuminate\Contracts\Container\Container;
use NunoMaduro\Larastan\Properties\ReflectionTypeContainer;

/**
 * @internal
 */
class Scope extends BaseScope
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function getType(Expr $node): Type
    {
        $type = parent::getType($node);

        /*
         * @todo Consider refactoring the code bellow.
         */
        if ($this->isContainer($type) && get_class($type) === Container::class) {
            $type = \Mockery::mock($type);
            $type->shouldReceive('isOffsetAccessible')
                ->andReturn(TrinaryLogic::createYes());
        }

        if ($type instanceof UnionType) {

            $types = $type->getTypes();
            foreach ($types as $key => $type) {
                if ($type instanceof ObjectWithoutClassType) {
                    $types[$key] = new MixedType();
                }
            }

            $type = new UnionType($types);
        }

        if ($type instanceof ObjectWithoutClassType) {
            $type = new MixedType();
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeFromArrayDimFetch(
        Expr\ArrayDimFetch $arrayDimFetch,
        Type $offsetType,
        Type $offsetAccessibleType
    ): Type {

        if ($arrayDimFetch->dim === null) {
            throw new ShouldNotHappenException();
        }

        $parentType = parent::getTypeFromArrayDimFetch($arrayDimFetch, $offsetType, $offsetAccessibleType);
        if ($this->isContainer($offsetAccessibleType)) {
            $dimType = $this->getType($arrayDimFetch->dim);
            if (! $dimType instanceof ConstantStringType) {
                return $parentType;
            }

            $concrete = $this->resolve($dimType->getValue());

            $type = is_object($concrete) ? get_class($concrete) : gettype($concrete);

            $reflectionType = new ReflectionTypeContainer($type);

            return TypehintHelper::decideTypeFromReflection(
                $reflectionType,
                null,
                is_object($concrete) ? get_class($concrete) : null
            );
        }

        return $parentType;
    }

    /**
     * Checks if the provided type implements
     * the Illuminate Container Contract.
     *
     * @param \PHPStan\Type\Type $type
     *
     * @return bool
     */
    private function isContainer(Type $type): bool
    {
        return ! (new ObjectType(Container::class))->isSuperTypeOf($type)
            ->no();
    }
}

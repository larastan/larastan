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

use ReflectionClass;
use function gettype;
use PHPStan\Type\Type;
use function get_class;
use function is_object;
use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\TypehintHelper;
use NunoMaduro\Larastan\Concerns;
use PHPStan\ShouldNotHappenException;
use PHPStan\Analyser\Scope as BaseScope;
use NunoMaduro\Larastan\Types\TypeResolver;
use Illuminate\Contracts\Container\Container;
use PHPStan\Type\Constant\ConstantStringType;
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

        if (substr(get_class($type), 0, 7) !== 'Mockery' && $this->isContainer($type)) {
            $type = \Mockery::mock($type);
            $type->shouldReceive('isOffsetAccessible')
                ->andReturn(TrinaryLogic::createYes());
        }

        return $this->getContainer()
            ->make(TypeResolver::class)
            ->resolveFrom($type);
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

            if ($concrete === null) {
                return new \PHPStan\Type\NullType();
            }

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
        foreach ($type->getReferencedClasses() as $referencedClass) {
            $isClassOrInterface = class_exists($referencedClass) || interface_exists($referencedClass);
            if ($isClassOrInterface && (new ReflectionClass($referencedClass))->implementsInterface(Container::class)) {
                return true;
            }
        }

        return false;
    }
}

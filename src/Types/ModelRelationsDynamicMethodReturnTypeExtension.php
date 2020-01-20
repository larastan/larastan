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

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class ModelRelationsDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    use HasContainer;

    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $variants = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
        $returnType = $variants->getReturnType();

        if (! $returnType instanceof ObjectType) {
            return false;
        }

        if (! (new ObjectType(Relation::class))->isSuperTypeOf($returnType)->yes()) {
            return false;
        }

        if (! $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName())) {
            return false;
        }

        if (count($variants->getParameters()) !== 0) {
            return false;
        }

        return ! in_array($methodReflection->getName(), [
            'hasOne', 'hasOneThrough', 'morphOne',
            'belongsTo', 'morphTo',
            'hasMany', 'hasManyThrough', 'morphMany',
            'belongsToMany', 'morphToMany', 'morphedByMany',
        ], true);
    }

    /**
     * @param MethodReflection $methodReflection
     * @param MethodCall       $methodCall
     * @param Scope            $scope
     *
     * @return Type
     * @throws BindingResolutionException
     * @throws ShouldNotHappenException
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        /** @var ObjectType $returnType */
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        return new RelationType(
            $returnType->getClassName(),
            get_class(
                $this->getContainer()->make($methodReflection->getDeclaringClass()->getName())
                    ->{$methodReflection->getName()}()
                    ->getRelated()
            )
        );
    }
}

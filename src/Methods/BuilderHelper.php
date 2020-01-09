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

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;

class BuilderHelper
{
    public function dynamicWhere(
        ClassReflection $classReflection,
        string $methodName,
        ?ObjectType $returnObject = null
    ): ?EloquentBuilderMethodReflection {
        if (! ($classReflection->getName() === Builder::class || $classReflection->isSubclassOf(Builder::class))) {
            return null;
        }

        if (! Str::startsWith($methodName, 'where')) {
            return null;
        }

        $methodReflection = $classReflection->getNativeMethod('dynamicWhere');

        /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $originalDynamicWhereVariant */
        $originalDynamicWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        return new EloquentBuilderMethodReflection(
            $methodName,
            $classReflection,
            [$originalDynamicWhereVariant->getParameters()[1]],
            $returnObject ?? new ObjectType(EloquentBuilder::class)
        );
    }
}

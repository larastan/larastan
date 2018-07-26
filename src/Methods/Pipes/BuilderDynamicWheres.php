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
use Mockery;
use Illuminate\Database\Query\Builder;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorSelector;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class BuilderDynamicWheres implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();
        $found = false;
        $isInstanceOfBuilder = $classReflection->getName() === Builder::class || $classReflection->isSubclassOf(
                Builder::class
            );

        if ($isInstanceOfBuilder && starts_with($passable->getMethodName(), 'where')) {
            $methodReflection = $classReflection->getNativeMethod('dynamicWhere');

            /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $originalDynamicWhereVariant */
            $originalDynamicWhereVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

            $variant = new FunctionVariantWithPhpDocs(
                [$originalDynamicWhereVariant->getParameters()[1]],
                $originalDynamicWhereVariant->isVariadic(),
                $originalDynamicWhereVariant->getReturnType(),
                $originalDynamicWhereVariant->getPhpDocReturnType(),
                $originalDynamicWhereVariant->getNativeReturnType()
            );

            $methodReflection = Mockery::mock($methodReflection);
            /* @var \Mockery\MockInterface $methodReflection */
            $methodReflection->shouldReceive('getVariants')
                ->andReturn([$variant]);

            $methodReflection->shouldReceive('isStatic')
                ->andReturn(true);

            $passable->setMethodReflection($methodReflection);

            $found = true;
        }

        if (! $found) {
            $next($passable);
        }
    }
}

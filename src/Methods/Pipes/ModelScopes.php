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
use Illuminate\Database\Eloquent\Model;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class ModelScopes implements PipeContract
{
    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflection = $passable->getClassReflection();

        $scopeMethodName = 'scope'.ucfirst($passable->getMethodName());

        $found = false;

        if ($classReflection->isSubclassOf(Model::class) && $classReflection->hasNativeMethod($scopeMethodName)) {
            /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $variant */
            $methodReflection = $classReflection->getNativeMethod($scopeMethodName);

            $variant = $methodReflection->getVariants()[0];
            $parameters = $variant->getParameters();
            unset($parameters[0]); // The query argument.

            $variant = Mockery::mock($variant);
            $variant->shouldReceive('getParameters')
                ->andReturn($parameters);

            $methodReflection = Mockery::mock($methodReflection);

            $methodReflection->shouldReceive('isStatic')
                ->andReturn(true);

            /* @var \Mockery\MockInterface $methodReflection */
            $methodReflection->shouldReceive('getVariants')
                ->andReturn([$variant]);

            $passable->setMethodReflection($methodReflection);

            $found = true;
        }

        if (! $found) {
            $next($passable);
        }
    }
}

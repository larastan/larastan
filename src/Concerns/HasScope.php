<?php

declare(strict_types=1);

/**
 * This file is part of Laravel Code Analyse.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\LaravelCodeAnalyse\Concerns;

use Mockery;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

/**
 * @internal
 */
trait HasScope
{
    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return parent::hasMethod($classReflection, $this->getScopeMethodName($methodName));
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $methodReflection = parent::getMethod($classReflection, $this->getScopeMethodName($methodName));

        return $this->getScopeMethodReflection($methodReflection);
    }

    /**
     * @param  string $originalMethod
     *
     * @return string
     */
    public function getScopeMethodName(string $originalMethod): string
    {
        return 'scope'.ucfirst($originalMethod);;
    }

    /**
     * @param  \PHPStan\Reflection\MethodReflection $methodReflection
     *
     * @return \PHPStan\Reflection\MethodReflection
     */
    public function getScopeMethodReflection(MethodReflection $methodReflection): MethodReflection
    {
        /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $variant */
        $variant = $methodReflection->getVariants()[0];
        $parameters = $variant->getParameters();
        unset($parameters[0]); // The query argument.

        $variant = Mockery::mock($variant);
        $variant->shouldReceive('getParameters')
            ->andReturn($parameters);

        /** @var \Mockery\MockInterface $methodReflection */
        $methodReflection->shouldReceive('getVariants')
            ->andReturn([$variant]);

        return $methodReflection;
    }
}
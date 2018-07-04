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

namespace NunoMaduro\LaravelCodeAnalyse\Database\Eloquent;

use Mockery;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

/**
 * @internal
 */
class ModelScopeMethodExtension extends ModelMethodExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $scopeMethodName = 'scope'.ucfirst($methodName);

        return parent::hasMethod($classReflection, $scopeMethodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $scopeMethodName = 'scope'.ucfirst($methodName);

        $methodReflection = parent::getMethod($classReflection, $scopeMethodName);

        /** @var \PHPStan\Reflection\FunctionVariantWithPhpDocs $variant */
        $variant = $methodReflection->getVariants()[0];
        $parameters = $variant->getParameters();
        unset($parameters[0]); // The query argument.

        $variant = Mockery::mock($variant);
        $variant->shouldReceive('getParameters')
            ->andReturn($parameters);

        $methodReflection->shouldReceive('getVariants')
            ->andReturn([$variant]);

        return $methodReflection;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection): array
    {
        return [$classReflection->getName()];
    }
}

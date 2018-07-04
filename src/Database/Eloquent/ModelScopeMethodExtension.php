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

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;

final class ModelScopeMethodExtension extends ModelMethodExtension
{
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        $scopeMethodName = 'scope'.ucfirst($methodName);

        return parent::hasMethod($classReflection, $scopeMethodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        $scopeMethodName = 'scope'.ucfirst($methodName);

        return parent::getMethod($classReflection, $scopeMethodName);
    }
}

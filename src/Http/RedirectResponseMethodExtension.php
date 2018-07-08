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

namespace NunoMaduro\Larastan\Http;

use Mockery;
use Illuminate\Http\RedirectResponse;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use NunoMaduro\Larastan\AbstractExtension;

/**
 * @internal
 */
final class RedirectResponseMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subject(ClassReflection $classReflection, string $methodName): array
    {
        return [RedirectResponse::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection, string $methodName): array
    {
        return [
            RedirectResponse::class,
        ];
    }

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

        return $methodReflection;
    }

    /**
     * @param  string $originalMethod
     *
     * @return string
     */
    public function getScopeMethodName(string $originalMethod): string
    {
        return 'with';
    }
}

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

namespace NunoMaduro\Larastan\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use PHPStan\Reflection\ClassReflection;
use Illuminate\Contracts\Auth\StatefulGuard;
use NunoMaduro\Larastan\AbstractExtension;

/**
 * @internal
 */
final class AuthManagerMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subject(ClassReflection $classReflection, string $methodName): array
    {
        return [AuthManager::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection, string $methodName): array
    {
        return [
            Guard::class,
            StatefulGuard::class,
        ];
    }
}

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

namespace NunoMaduro\LaravelCodeAnalyse\Auth;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Guard;
use PHPStan\Reflection\ClassReflection;
use Illuminate\Contracts\Auth\StatefulGuard;
use NunoMaduro\LaravelCodeAnalyse\AbstractExtension;

final class AuthManagerExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subject(): string
    {
        return AuthManager::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection): array
    {
        return [
            Guard::class,
            StatefulGuard::class,
        ];
    }
}

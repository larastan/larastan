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

namespace NunoMaduro\Larastan\Contracts;

use Illuminate\Container\Container;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\Larastan\AbstractExtension;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * @internal
 */
final class UserContractsMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subjects(ClassReflection $classReflection, string $methodName): array
    {
        return [Authenticatable::class, CanResetPassword::class, Authorizable::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function mixins(ClassReflection $classReflection, string $methodName): array
    {
        $config = ($this->container ?? Container::getInstance())->get('config');

        $userModel = $config->get('auth.providers.users.model');

        return [$userModel];
    }
}

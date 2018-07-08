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

namespace NunoMaduro\Larastan\Contracts\Auth;

use Illuminate\Container\Container;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\Larastan\AbstractExtension;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container as ContainerContract;

/**
 * @internal
 */
final class AuthenticatableMethodExtension extends AbstractExtension
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;

    /**
     * AuthenticatableExtension constructor.
     *
     * @param \Illuminate\Contracts\Container\Container|null $container
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function subject(ClassReflection $classReflection, string $methodName): array
    {
        return [Authenticatable::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection, string $methodName): array
    {
        $config = ($this->container ?? Container::getInstance())->get('config');

        $userModel = $config->get('auth.providers.users.model');

        return [$userModel];
    }
}

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

namespace NunoMaduro\Larastan\Middlewares;

use Closure;
use NunoMaduro\Larastan\Passable;
use NunoMaduro\Larastan\Concerns\HasContainer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable;

/**
 * @internal
 */
final class Auths
{
    use HasContainer;

    /**
     * @var []string
     */
    private $classes = [
        Authenticatable::class,
        CanResetPassword::class,
        Authorizable::class,
    ];

    /**
     * @param \NunoMaduro\Larastan\Passable $passable
     * @param \Closure $next
     *
     * @return void
     */
    public function handle(Passable $passable, Closure $next): void
    {
        $classReflectionName = $passable->getClassReflection()
            ->getName();

        $found = false;

        if (in_array($classReflectionName, $this->classes)) {
            $config = $this->resolve('config');

            $userModel = $config->get('auth.providers.users.model');

            $found = $passable->inception($userModel);
        }

        if (! $found) {
            $next($passable);
        }
    }
}

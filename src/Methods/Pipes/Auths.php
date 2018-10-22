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
use function in_array;
use NunoMaduro\Larastan\Concerns;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable;
use NunoMaduro\Larastan\Contracts\Methods\PassableContract;
use NunoMaduro\Larastan\Contracts\Methods\Pipes\PipeContract;

/**
 * @internal
 */
final class Auths implements PipeContract
{
    use Concerns\HasContainer;

    /**
     * @var string[]
     */
    private $classes = [
        Authenticatable::class,
        CanResetPassword::class,
        Authorizable::class,
    ];

    /**
     * {@inheritdoc}
     */
    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflectionName = $passable->getClassReflection()
            ->getName();

        $found = false;

        if (in_array($classReflectionName, $this->classes, true)) {
            $config = $this->resolve('config');

            $userModel = $config->get('auth.providers.users.model');

            if ($userModel) {
                $found = $passable->sendToPipeline($userModel);
            }
        }

        if (! $found) {
            $next($passable);
        }
    }
}

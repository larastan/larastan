<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use function in_array;
use NunoMaduro\Larastan\Concerns;
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

            $authModel = $this->getAuthModel($config);

            if ($authModel) {
                $found = $passable->sendToPipeline($authModel);
            }
        } elseif ($classReflectionName === \Illuminate\Contracts\Auth\Factory::class || $classReflectionName === \Illuminate\Auth\AuthManager::class) {
            $found = $passable->sendToPipeline(
                \Illuminate\Contracts\Auth\Guard::class
            );
        }

        if (! $found) {
            $next($passable);
        }
    }

    /**
     * Returns the default auth model from config.
     *
     * @return string|null
     */
    private function getAuthModel(ConfigRepository $config)
    {
        if (
            ! ($guard = $config->get('auth.defaults.guard')) ||
            ! ($provider = $config->get('auth.guards.'.$guard.'.provider')) ||
            ! ($authModel = $config->get('auth.providers.'.$provider.'.model'))
        ) {
            return null;
        }

        return $authModel;
    }
}

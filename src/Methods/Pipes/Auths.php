<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods\Pipes;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\Guard;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Contracts\Methods\PassableContract;
use Larastan\Larastan\Contracts\Methods\Pipes\PipeContract;

use function in_array;

/** @internal */
final class Auths implements PipeContract
{
    use Concerns\HasContainer;
    use Concerns\LoadsAuthModel;

    /** @var string[] */
    private array $classes = [
        Authenticatable::class,
        CanResetPassword::class,
        Authorizable::class,
    ];

    public function handle(PassableContract $passable, Closure $next): void
    {
        $classReflectionName = $passable->getClassReflection()
            ->getName();

        $found = false;

        $config = $this->resolve('config');

        if ($config !== null && in_array($classReflectionName, $this->classes, true)) {
            $authModels = $this->getAuthModels($config);

            foreach ($authModels as $authModel) {
                if ($passable->sendToPipeline($authModel)) {
                    $found = true;

                    break;
                }
            }
        } elseif ($classReflectionName === Factory::class || $classReflectionName === AuthManager::class) {
            $found = $passable->sendToPipeline(
                Guard::class,
            );
        }

        if ($found) {
            return;
        }

        $next($passable);
    }
}

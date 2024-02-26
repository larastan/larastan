<?php

declare(strict_types=1);

namespace Larastan\Larastan\Concerns;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

use function array_keys;
use function array_reduce;
use function in_array;
use function is_array;

trait LoadsAuthModel
{
    /** @phpstan-return list<class-string> */
    private function getAuthModels(ConfigRepository $config, string|null $guard = null): array
    {
        $guards    = $config->get('auth.guards');
        $providers = $config->get('auth.providers');

        if (! is_array($guards) || ! is_array($providers)) {
            return [];
        }

        return array_reduce(
            $guard === null ? array_keys($guards) : [$guard],
            static function ($carry, $guardName) use ($guards, $providers) {
                $provider  = $guards[$guardName]['provider'] ?? null;
                $authModel = $providers[$provider]['model'] ?? null;

                if (! $authModel || in_array($authModel, $carry, strict: true)) {
                    return $carry;
                }

                $carry[] = $authModel;

                return $carry;
            },
            initial: [],
        );
    }
}

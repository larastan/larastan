<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Concerns;

use function array_key_exists;
use Illuminate\Config\Repository as ConfigRepository;
use function in_array;
use function is_array;

trait LoadsAuthModel
{
    /** @phpstan-return array<int, class-string> */
    private function getAuthModels(ConfigRepository $config, ?string $guard = null): array
    {
        if (
            $guard !== null &&
            ($provider = $config->get('auth.guards.'.$guard.'.provider')) &&
            ($authModel = $config->get('auth.providers.'.$provider.'.model'))
        ) {
            return [$authModel];
        }

        $guards = $config->get('auth.guards');
        $providers = $config->get('auth.providers');

        if (! is_array($guards) || ! is_array($providers)) {
            return [];
        }

        return array_reduce(
            array_keys($guards),
            function (array $carry, $guardName) use ($guards, $providers): array {
                $provider = $guards[$guardName]['provider'] ?? null;
                if (! array_key_exists($provider, $providers)) {
                    return $carry;
                }

                $authModel = $providers[$provider]['model'] ?? null;
                if (! $authModel) {
                    return $carry;
                }

                if (in_array($authModel, $carry, true)) {
                    return $carry;
                }

                $carry[] = $authModel;

                return $carry;
            },
            [],
        );
    }
}

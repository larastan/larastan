<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

/**
 * Prevents infinite recursion when resolving methods/properties through
 * class-reflection extensions. Modeled after PHPStan's RecursionGuard.
 *
 * @internal
 *
 * @see https://github.com/phpstan/phpstan-src/blob/2.2.x/src/Type/RecursionGuard.php
 */
final class RecursionGuard
{
    /** @var array<string, true> */
    private static array $context = [];

    /**
     * @param callable(): T $callback
     *
     * @return T|null
     *
     * @template T
     */
    public static function run(string $key, callable $callback): mixed
    {
        if (isset(self::$context[$key])) {
            return null;
        }

        try {
            self::$context[$key] = true;

            return $callback();
        } finally {
            unset(self::$context[$key]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use function version_compare;

final class LaravelVersion
{
    public static function hasLaravel1115Generics(): bool
    {
        return version_compare(self::getLaravelVersion(), '11.15.0', '>=');
    }

    public static function getBuilderModelGenericName(): string
    {
        return self::hasLaravel1115Generics() ? 'TModel' : 'TModelClass';
    }

    public static function hasCollectedByAttribute(): bool
    {
        return version_compare(self::getLaravelVersion(), '11.28.0', '>=');
    }

    private static function getLaravelVersion(): string
    {
        return LARAVEL_VERSION;
    }
}

<?php

declare(strict_types=1);

use Carbon\Carbon;

Carbon::macro('foo', static function (): string {
    return 'foo';
});

\Illuminate\Database\Eloquent\Builder::macro('globalCustomMacro', function (string $arg = 'foobar', int $b = 5): string {
    return $arg;
});

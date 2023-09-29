<?php

declare(strict_types=1);

use Carbon\Carbon;

Carbon::macro('foo', static function (): string {
    return 'foo';
});

enum SomeEnum {
    case A;
    case B;
}

\Illuminate\Database\Eloquent\Builder::macro('globalCustomMacro', function (string $arg = 'foobar', int $b = 5, SomeEnum $enum = SomeEnum::A): string {
    return $arg;
});

\Illuminate\Support\Facades\Route::macro('facadeMacro', function (): int {
    return 5;
});

\Illuminate\Auth\SessionGuard::macro('sessionGuardMacro', function (): int {
    return 5;
});

\Illuminate\Auth\RequestGuard::macro('requestGuardMacro', function (): int {
    return 5;
});

\Illuminate\Support\Str::macro('trimMacro', 'trim');
\Illuminate\Support\Str::macro('asciiAliasMacro', \Illuminate\Support\Str::class.'::ascii');

class CustomCollectionMacro
{
    public function registerMacro()
    {
        \Illuminate\Support\Collection::macro('customCollectionMacro', [$this, 'customMacro']);
    }

    public function customMacro(): string
    {
        return 'customMacro';
    }
}

(new CustomCollectionMacro)->registerMacro();

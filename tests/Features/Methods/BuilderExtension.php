<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;
use Illuminate\Database\Eloquent\Builder;

Builder::macro('foo', static function (): string {
    return 'foo';
});

class BuilderExtension
{
    public function testBuilderMacroCalledStatically(): string
    {
        return Builder::foo();
    }

    public function testBuilderMacroCalledDynamically(): string
    {
        return User::query()->foo();
    }
}

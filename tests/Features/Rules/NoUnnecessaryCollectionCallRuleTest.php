<?php

declare(strict_types=1);

namespace Tests\Features\Rules;

use App\User;
use Illuminate\Support\Collection;

class NoUnnecessaryCollectionCallRuleTest
{
    public function testPluckComputedProperty(): Collection
    {
        return User::all()->pluck('allCapsName');
    }

    public function testCallPluckCorrect(): Collection
    {
        return User::pluck('id');
    }

    public function testCallCountProperly(): int
    {
        return User::where('id', '>', 5)->count();
    }

    public function testNotAnalyzable(): int
    {
        $x = 'get';

        return User::where('id', '>', 1)->{$x}()->count();
    }
}

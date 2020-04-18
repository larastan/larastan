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

    /**
     * Can't analyze the closure as a parameter to contains, so should not throw any error.
     * @return bool
     */
    public function testContainsClosure(): bool
    {
        return User::where('id', '>', 1)->get()->contains(function (User $user): bool {
            return $user->id === 2;
        });
    }

    /**
     * Can't analyze the closure as a parameter to first, so should not throw any error.
     * @return User|null
     */
    public function testFirstClosure(): ?User
    {
        return User::where('id', '>', 1)->get()->first(function (User $user): bool {
            return $user->id === 2;
        });
    }
}

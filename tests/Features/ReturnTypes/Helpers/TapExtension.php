<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use App\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Foundation\Application;
use NunoMaduro\Larastan\ApplicationResolver;

class TapExtension
{
    /*public function testTapClosure(): User
    {
        return tap(new User, function (User $user) {
            $user->name = 'Daan Raatjes';
            $user->save();
        });
    }*/

    public function testTapProxy(): int
    {
        // User::firstOrFail()->hello();
        return tap(new User)->updatez(['name' => 'Taylor otwell']);
    }
}

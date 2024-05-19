<?php

namespace Bug1718;

use Illuminate\Http\Request;
use App\User;
use function PHPStan\Testing\assertType;

class NewRequest extends Request
{
    public function authorize(): bool
    {
        return parent::user() instanceof User;
    }

    public function user($guard = null): User
    {
        $user = parent::user($guard);
        if (!$user instanceof User) {
            abort(403);
        }

        return $user;
    }
}

function test(NewRequest $newRequest, Request $request): void
{
    assertType('App\User', $newRequest->user());
    assertType('App\Admin|App\User|null', $request->user());
}

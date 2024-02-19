<?php

namespace Bug1718;

use Illuminate\Foundation\Http\FormRequest;
use App\User;
use function PHPStan\Testing\assertType;

class AuthedRequest extends FormRequest
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

function test(AuthedRequest $request, FormRequest $formRequest): void
{
    assertType('App\User', $request->user());
    assertType('App\Admin|App\User|null', $formRequest->user());
}

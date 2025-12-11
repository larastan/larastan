<?php

namespace Tests\Rules\Data;

use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;

class FooRequest extends Request {}

class NoAuthHelperInRequestScopeRuleTestData
{
    public function __construct(private AuthManager $authManager)
    {
    }

    public function good(FooRequest $request)
    {
        return $request->user() ? true : false;
    }

    public function badCheck(FooRequest $request): bool
    {
        return $request->user() !== null;
    }

    public function badUser(FooRequest $request)
    {
        return $request->user();
    }

    public function badGuest(Request $request): bool
    {
        return $request->user() === null;
    }

    public function goodWithNoRequest(): bool
    {
        return auth()->check();
    }

    public function goodWithProperty(Request $request): bool
    {
        return $this->authManager->check();
    }
}

class CustomRequest extends Request
{
    public function foo(): bool
    {
        return $this->user() !== null;
    }
}

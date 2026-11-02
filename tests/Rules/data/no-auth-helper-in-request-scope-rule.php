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
        return auth()->check();
    }

    public function badUser(FooRequest $request)
    {
        return auth()->user();
    }

    public function badGuest(Request $request): bool
    {
        return auth()->guest();
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
        return auth()->check();
    }
}

<?php

namespace Tests\Rules\Data;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoAuthFacadeInRequestScopeRuleData
{
    public function good(Request $request)
    {
        return $request->user() ? true : false;
    }

    public function badCheck(Request $request)
    {
        return $request->user() !== null;
    }

    public function badUser(Request $request)
    {
        return $request->user();
    }

    public function badGuest(Request $request)
    {
        return $request->user() === null;
    }

    public function goodWithNoRequest()
    {
        return Auth::check();
    }
}

class CustomRequest extends Request
{
    public function foo(): bool
    {
        return $this->user() !== null;
    }
}

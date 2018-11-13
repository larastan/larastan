<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Contracts\Auth\UserProvider;
use Closure;
use Illuminate\Auth\AuthManager;

class AuthExtension
{
    /*
    It's not working as it's returning App\User|Illuminate\Contracts\Auth\Authenticatable|null
    and expected is Illuminate\Contracts\Auth\Authenticatable|null

    public function testUser(): ?Authenticatable
    {
        return Auth::user();
    }
     */

    public function testGuard(): Guard
    {
        return Auth::guard();
    }

    public function testShouldUse(): void
    {
        Auth::shouldUse('foo');
    }

    public function testCheck(): bool
    {
        return Auth::check();
    }

    public function testGuest(): bool
    {
        return Auth::guest();
    }

    public function testId(): ?int
    {
        return Auth::id();
    }

    public function testValidate(): bool
    {
        return Auth::validate();
    }
    /*
    setUser expects parameter of type \Illuminate\Contracts\Auth\Authenticatable
    so I am confused here.

    public function testSetUser(): void
    {
        Auth::setUser(Auth::user());
    }
     */

    public function testAttempt(): bool
    {
        return Auth::attempt();
    }

    public function testOnce(): bool
    {
        return Auth::once();
    }

    /*
    setUser expects parameter of type \Illuminate\Contracts\Auth\Authenticatable
    so I am confused here.

    public function testLogin(): void
    {
        return Auth::login(Auth::user());
    }
     */

    /*
    These both functions returing Object along with bool which is not added in
    return type declaration to check both. You can only check for null along
    with any other declaration.

    public function testLoginUsingId(): ?Authenticatable
    {
        return Auth::loginUsingId(1);
    }

    public function testOnceUsingId(): bool
    {
        return Auth::onceUsingId(1);
    }
     */

    public function testViaRemember(): bool
    {
        return Auth::viaRemember();
    }

    public function testLogout(): void
    {
        Auth::logout();
    }

    public function testOnceBasic(): ?Response
    {
        return Auth::onceBasic();
    }

    public function testLogoutOtherDevices(): ?bool
    {
        return Auth::logoutOtherDevices('password');
    }

    public function testCreateUserProvider(): ?UserProvider
    {
        return Auth::createUserProvider();
    }

    /*
    Require Closure as a second parameter so Do I need to mock it?

    public function testExtend(): ?AuthManager
    {
        return Auth::extend('driver', new Closure);
    }

    public function testProvider(): ?AuthManager
    {
        return Auth::provider('foo', new Closure);
    }
     */

}

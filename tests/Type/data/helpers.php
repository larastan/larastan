<?php

namespace Helpers;

use App\User;
use Exception;
use NunoMaduro\Larastan\ApplicationResolver;
use function PHPStan\Testing\assertType;
use Throwable;

function appHelper()
{
    assertType('Illuminate\Foundation\Application', app());
    assertType('NunoMaduro\Larastan\ApplicationResolver', app(ApplicationResolver::class));
    assertType('Illuminate\Auth\AuthManager', app('auth'));
    assertType('NunoMaduro\Larastan\ApplicationResolver', resolve(ApplicationResolver::class));
    assertType('Illuminate\Auth\AuthManager', resolve('auth'));
}

function authHelper()
{
    assertType('Illuminate\Auth\AuthManager', auth());
    assertType('Illuminate\Contracts\Auth\Guard', auth()->guard('web'));
    assertType('Illuminate\Contracts\Auth\StatefulGuard', auth('web'));
    assertType('App\User|null', auth()->user());
    assertType('bool', auth()->check());
    assertType('App\User|null', auth()->guard('web')->user());
    assertType('App\User|null', auth('web')->user());
    assertType('App\Admin|null', auth()->guard('admin')->user());
    assertType('App\Admin|null', auth('admin')->user());
    assertType('int|string|null', auth()->id());
    assertType('int|string|null', auth('web')->id());
    assertType('int|string|null', auth('admin')->id());
    assertType('Illuminate\Contracts\Auth\Authenticatable|false', auth()->loginUsingId(1));
    assertType('void', auth()->login(new User()));
}

function nowAndToday()
{
    assertType('Illuminate\Support\Carbon', now());
    assertType('Illuminate\Support\Carbon', today());
}

function redirectHelper()
{
    assertType('Illuminate\Http\RedirectResponse', redirect('/'));
    assertType('Illuminate\Routing\Redirector', redirect());
}

function requestHelper()
{
    assertType('Illuminate\Http\Request', request());
    assertType('mixed', request('foo'));
    assertType('array<string>', request(['foo', 'bar']));
}

function rescueHelper()
{
    assertType('string|null', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }));

    assertType('string', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, 'failed'));

    assertType('int|string', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, function () {
        return 0;
    }));

    assertType('int|string', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, function (Throwable $e) {
        return 0;
    }));

    assertType('string', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, 'failed', false));
}

function responseHelper()
{
    assertType('Illuminate\Http\Response', response('foo'));
    assertType('Illuminate\Contracts\Routing\ResponseFactory', response());
}

function retryHelper()
{
    assertType('void', retry(3, function () {
    }));

    assertType('int', retry(3, function (): int {
        return 5;
    }));

    assertType('App\User|null', retry(5, function (): ?User {
        return User::first();
    }, 0, function (): bool {
        return true;
    }));

    assertType('bool', retry(5, function (int $attempt): bool {
        return false;
    }, 0, function (Exception $e): bool {
        return true;
    }));
}

function tapHelper()
{
    assertType('App\User', tap(new User(), function (User $user): void {
        $user->name = 'Can Vural';
        $user->save();
    }));

    assertType('Illuminate\Support\HigherOrderTapProxy<App\User>', tap(new User()));
    assertType('App\User', tap(new User())->update(['name' => 'Taylor Otwell']));
}

function urlHelper()
{
    assertType('string', url('/path'));
    assertType('Illuminate\Contracts\Routing\UrlGenerator', url());
}

function validatorHelper()
{
    assertType('Illuminate\Contracts\Validation\Factory', validator());
    assertType('Illuminate\Contracts\Validation\Validator&Illuminate\Validation\Validator', validator(['foo' => 'bar'], ['foo' => 'required']));
    assertType('array', validator(['foo' => 'bar'], ['foo' => 'required'])->valid());
}

function valueHelper()
{
    assertType('App\User|null', value(function (): ?User {
        return User::first();
    }));

    assertType('5', value(5));
}

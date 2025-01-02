<?php

namespace Helpers;

use App\User;
use Exception;
use Illuminate\Support\Str;
use Larastan\Larastan\ApplicationResolver;
use Throwable;

use function PHPStan\Testing\assertType;

function test(?int $value = 0): void
{
    assertType('Illuminate\Foundation\Application', app());
    assertType('Larastan\Larastan\ApplicationResolver', app(ApplicationResolver::class));
    assertType('Illuminate\Auth\AuthManager', app('auth'));
    assertType('Larastan\Larastan\ApplicationResolver', resolve(ApplicationResolver::class));
    assertType('Illuminate\Auth\AuthManager', resolve('auth'));

    assertType('Illuminate\Auth\AuthManager', auth());
    assertType('Illuminate\Contracts\Auth\Guard', auth()->guard('web'));
    assertType('Illuminate\Contracts\Auth\StatefulGuard', auth('web'));
    assertType('App\Admin|App\User|null', auth()->user());
    assertType('bool', auth()->check());
    assertType('App\User|null', auth()->guard('web')->user());
    assertType('App\User|null', auth('web')->user());
    assertType('App\Admin|null', auth()->guard('admin')->user());
    assertType('App\Admin|null', auth('admin')->user());
    assertType('int|string|null', auth()->id());
    assertType('int|string|null', auth('web')->id());
    assertType('int|string|null', auth('admin')->id());
    assertType('Illuminate\Contracts\Auth\Authenticatable|false', auth()->loginUsingId(1));
    assertType('null', auth()->login(new User()));

    assertType('Illuminate\Support\Carbon', now());
    assertType('Illuminate\Support\Carbon', today());

    assertType('Illuminate\Http\RedirectResponse', redirect('/'));
    assertType('Illuminate\Routing\Redirector', redirect());

    assertType('Illuminate\Http\Request', request());
    assertType('mixed', request('foo'));
    assertType('array<string>', request(['foo', 'bar']));

    assertType('\'ok\'|null', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }));

    assertType('\'failed\'|\'ok\'', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, 'failed'));

    assertType('0|\'ok\'', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, function () {
        return 0;
    }));

    assertType('0|\'ok\'', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, function (Throwable $e) {
        return 0;
    }));

    assertType('\'failed\'|\'ok\'', rescue(function () {
        if (mt_rand(0, 1)) {
            throw new Exception();
        }

        return 'ok';
    }, 'failed', false));

    assertType('Illuminate\Http\Response', response('foo'));
    assertType('Illuminate\Contracts\Routing\ResponseFactory', response());

    assertType('null', retry(3, function () {
    }));

    assertType('5', retry(3, function (): int {
        return 5;
    }));

    assertType('App\User|null', retry(5, function (): ?User {
        return User::first();
    }, 0, function (): bool {
        return true;
    }));

    assertType('false', retry(5, function (int $attempt): bool {
        return false;
    }, 0, function (Exception $e): bool {
        return true;
    }));

    assertType('Illuminate\Support\Stringable', str('foo'));
    assertType('mixed', str());

    assertType("'Laravel'", Str::replace('foo', 'bar', 'Laravel'));
    assertType("array{'Laravel', 'Framework'}", Str::replace('foo', 'bar', ['Laravel', 'Framework']));
    assertType('array<int|string, string>', Str::replace('foo', 'bar', collect(['Laravel', 'Framework'])));

    assertType('App\User', tap(new User(), function (User $user): void {
        $user->name = 'Can Vural';
        $user->save();
    }));

    assertType('Illuminate\Support\HigherOrderTapProxy<App\User>', tap(new User()));
    assertType('App\User', tap(new User())->update(['name' => 'Taylor Otwell']));
    assertType('Illuminate\Contracts\Validation\Validator&Illuminate\Validation\Validator', tap(validator([], []))->addReplacers());

    assertType('string', url('/path'));
    assertType('Illuminate\Contracts\Routing\UrlGenerator', url());

    assertType('Illuminate\Contracts\Validation\Factory', validator());
    assertType('Illuminate\Contracts\Validation\Validator&Illuminate\Validation\Validator', validator(['foo' => 'bar'], ['foo' => 'required']));
    assertType('array', validator(['foo' => 'bar'], ['foo' => 'required'])->valid());

    assertType('App\User|null', value(function (): ?User {
        return User::first();
    }));

    assertType('5', value(5));

    assertType('array<mixed>|null', transform(User::first(), fn (User $user) => $user->toArray()));
    assertType('array<mixed>', transform(User::sole(), fn (User $user) => $user->toArray()));

    // falls back to default if provided
    assertType("1|'default'", transform(optional(), fn () => 1, 'default'));
    // default as callable
    assertType('1|\'string\'', transform(optional(), fn () => 1, fn () => 'string'));

    // non empty values
    assertType('1', transform('filled', fn () => 1));
    assertType('1', transform(['filled'], fn () => 1));
    assertType('1', transform(new User(), fn () => 1));

    // "empty" values
    assertType('null', transform(null, fn () => 1));
    assertType('null', transform('', fn () => 1));
    assertType('null', transform([], fn () => 1));

    if (filled($value)) {
        assertType('int', $value);
    } else {
        assertType('int|null', $value);
    }

    if (blank($value)) {
        assertType('int|null', $value);
    } else {
        assertType('int', $value);
    }

    assertType('bool|string|null', env('foo'));
    assertType('bool|string|null', env('foo', null));
    assertType('120|bool|string', env('foo', 120));
    assertType('bool|string', env('foo', ''));

    assertType('true', literal(true));
    assertType('int<0, 10>', literal(random_int(0,10)));
    assertType("object{bar: 'bar'}&stdClass", literal(bar: "bar"));
    assertType("object{foo: 22, bar: 'bar'}&stdClass", literal(foo: 22, bar: "bar"));
    assertType("object{foo: int<0, 5>, bar: 'bar'}&stdClass", literal(foo: random_int(0,5), bar: "bar"));
    assertType("object{}&stdClass", literal(new \stdClass));
    assertType("object{}&stdClass", literal());
    assertType("object{0: 'bar', 1: 'foo'}&stdClass", literal('bar', 'foo'));
    assertType("object{0: 4, bar: 'foo'}&stdClass", literal(4, bar:'foo'));
    assertType("App\User", literal(new User()));
    assertType("array{foo: 22, bar: 'bar'}", literal(['foo' => 22, 'bar' => "bar"]));
    assertType("object{0: 5, 1: 7}&stdClass", literal(...[5,7]));
    assertType("object{foo: 22, bar: 'bar'}&stdClass", literal(...['foo' => 22, 'bar' => "bar"]));
}

/**
 * @param array{loo:'loo'}|array{foo:'foo',bar:'baa'} $parameter
 */
function testUnion($parameter): int {
    assertType("(object{foo: 'foo', bar: 'baa'}&stdClass)|(object{loo: 'loo'}&stdClass)", literal(...$parameter));

    return 0;
};

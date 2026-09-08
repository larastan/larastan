<?php

declare(strict_types=1);

namespace FormRequestAccessors;

use App\Http\Requests\AccessorRequest;
use App\Http\Requests\FooRequest;
use App\Http\Requests\OverriddenInputRequest;
use App\Http\Requests\SafeReturnRequest;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

function testInput(AccessorRequest $request, SafeReturnRequest $safeReturnRequest, FormRequest $formRequest, string $key): void
{
    assertType('non-empty-string', $request->input('name'));
    assertType('non-empty-string', $request->input('name', 0));
    assertType('string|null', $request->input('nickname'));
    assertType('0|string', $request->input('nickname', 0));
    assertType('5|string', $request->input('nickname', static fn (): int => 5));
    assertType("'a'|'b'", $request->input('mode'));
    assertType('float|int<1, 5>|numeric-string', $request->input('count'));
    assertType('float|int<min, 120>|numeric-string', $request->input('profile.age'));
    assertType('1|string', $request->input('profile.city', 1));
    assertType('array{age: float|int<min, 120>|numeric-string, city?: string, ...}', $request->input('profile'));
    assertType('array<string>|null', $request->input('tags'));
    assertType('string|null', $request->input('tags.0'));
    assertType('mixed', $request->input('unknown'));
    assertType('mixed', $request->input('unknown.child'));
    assertType('mixed', $request->input($key));
    assertType('mixed', $request->input('profile.*'));
    assertType(
        'array{name: non-empty-string, nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string, ...}, excluded?: mixed, unknown: mixed, ...}',
        $safeReturnRequest->input(),
    );
    assertType(
        'array{name: non-empty-string, nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string, ...}, excluded?: mixed, unknown: mixed, ...}',
        $safeReturnRequest->input(null),
    );
    assertType('mixed', $formRequest->input('name'));

    // Uploaded files are not part of input().
    assertType('mixed', $request->input('avatar'));
    assertType('mixed', $request->input('photos'));
    assertType('mixed', $request->input('photos.0'));
    assertType('mixed', $request->input());
}

function testInteger(AccessorRequest $request, FooRequest $fooRequest, string $key): void
{
    assertType('int<1, 5>', $request->integer('count'));
    assertType('int<1, 5>', $request->integer('count', 9));
    assertType('int<1, max>', $request->integer('amount'));
    assertType('int<min, 120>', $request->integer('profile.age'));
    assertType('int', $request->integer('digits'));
    assertType('int', $request->integer('name'));
    assertType('0', $request->integer('mode'));
    assertType('0|1', $request->integer('active'));
    assertType('int', $request->integer('nickname'));
    assertType('int', $request->integer('unknown'));
    assertType('int', $request->integer($key));
    assertType('int', $request->integer('avatar'));
    assertType('int<0, 20>', $fooRequest->integer('limit'));
    assertType('int<1, 20>', $fooRequest->integer('limit', 5));
    assertType('30|int<1, 20>', $fooRequest->integer('limit', 30));
    assertType('int<0, 20>', $fooRequest->integer('limit', null));
    assertType('1|2', $fooRequest->integer('priority'));
    assertType('int', $fooRequest->integer('rating'));
}

function testBoolean(AccessorRequest $request, FooRequest $fooRequest, string $key): void
{
    assertType('true', $request->boolean('terms'));
    assertType('false', $request->boolean('marketing'));
    assertType('bool', $request->boolean('active'));
    assertType('bool', $request->boolean('nickname'));
    assertType('bool', $request->boolean('nickname', true));
    assertType('bool', $request->boolean('name'));
    assertType('bool', $request->boolean('unknown'));
    assertType('bool', $request->boolean($key));
    assertType('bool', $request->boolean());
    assertType('bool', $request->boolean('avatar'));
    assertType('bool', $fooRequest->boolean('newsletter'));
    assertType('true', $fooRequest->boolean('newsletter', true));
    assertType('bool', $fooRequest->boolean('conditionallyAccepted'));
}

function testValidatedInput(AccessorRequest $request, SafeReturnRequest $safeReturnRequest, string $key): void
{
    assertType('non-empty-string', $safeReturnRequest->safe()->input('name'));
    assertType('string|null', $safeReturnRequest->safe()->input('nickname'));
    assertType('non-empty-string', $safeReturnRequest->safe()->input('profile.email'));
    assertType('float|int|numeric-string|null', $safeReturnRequest->safe()->input('profile.age'));
    assertType('null', $safeReturnRequest->safe()->input('missing'));
    assertType("'fallback'", $safeReturnRequest->safe()->input('missing', 'fallback'));
    assertType('mixed', $safeReturnRequest->safe()->input('unknown'));
    assertType('mixed', $safeReturnRequest->safe()->input($key));
    assertType(
        'array{name: non-empty-string, nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string}, unknown: mixed}',
        $safeReturnRequest->safe()->input(),
    );
    assertType('int', $safeReturnRequest->safe()->integer('profile.age'));
    assertType('0', $safeReturnRequest->safe()->integer('missing'));
    assertType('int<1, 5>', $request->safe()->integer('count'));
    assertType('true', $request->safe()->boolean('terms'));
    assertType('false', $request->safe()->boolean('missing'));
    assertType('Illuminate\\Http\\UploadedFile', $request->safe()->input('avatar'));
    assertType('int', $request->safe()->integer('avatar'));
    assertType('mixed', $request->safe()['name']);
}

function testOverrides(OverriddenInputRequest $request, AccessorRequest|OverriddenInputRequest $either): void
{
    assertType('mixed', $request->input('name'));
    assertType('int', $request->integer('name'));
    assertType('mixed', $either->input('name'));
    assertType('int', $either->integer('name'));
    assertType('bool', $either->boolean('name'));
}

function testUnions(AccessorRequest|SafeReturnRequest $either): void
{
    assertType('non-empty-string', $either->input('name'));
    assertType('int', $either->integer('name'));
    assertType('mixed', $either->input('count'));
    assertType('int', $either->integer('count'));
}

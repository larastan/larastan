<?php

declare(strict_types=1);

namespace FormRequestAccessors;

use App\Http\Requests\AccessorRequest;
use App\Http\Requests\AdditionalRulesRequest;
use App\Http\Requests\FooRequest;
use App\Http\Requests\OverriddenInputRequest;
use App\Http\Requests\RequestPriority;
use App\Http\Requests\RequestRole;
use App\Http\Requests\RequestStatus;
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

function testInteger(AccessorRequest $request, FooRequest $fooRequest, AdditionalRulesRequest $additionalRulesRequest, string $key): void
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
    assertType('int<6, max>', $additionalRulesRequest->integer('integerGreaterThanValue'));
    assertType('int<5, max>', $additionalRulesRequest->integer('numericGreaterThanValue'));
    assertType('int<min, -5>', $additionalRulesRequest->integer('numericLessThanValue'));
    assertType('int<5, 10>', $additionalRulesRequest->integer('integerComparisonBoundsValue'));
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

function testFloat(AccessorRequest $request, FooRequest $fooRequest, string $key): void
{
    assertType('float', $request->float('count'));
    assertType('float', $request->float('ratio'));
    assertType('float', $request->float('name'));
    assertType('0.0', $request->float('mode'));
    assertType('float', $request->float('nickname'));
    assertType('float', $fooRequest->float('limit'));
    assertType('float', $fooRequest->float('limit', 1.5));
    assertType('float', $request->float($key));
    assertType('float', $request->float('avatar'));
}

function testEnum(AccessorRequest $request, string $key): void
{
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->enum('status', RequestStatus::class));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->enum('status', RequestStatus::class, RequestStatus::Draft));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published|null', $request->enum('optionalStatus', RequestStatus::class));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->enum('optionalStatus', RequestStatus::class, RequestStatus::Draft));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->enum('optionalStatus', RequestStatus::class, static fn (): RequestStatus => RequestStatus::Draft));
    assertType('App\\Http\\Requests\\RequestPriority::High|App\\Http\\Requests\\RequestPriority::Low|null', $request->enum('priority', RequestPriority::class));
    assertType('null', $request->enum('mode', RequestStatus::class));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published|null', $request->enum('name', RequestStatus::class));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->enum('name', RequestStatus::class, RequestStatus::Draft));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published|null', $request->enum('unknown', RequestStatus::class));
    assertType('App\\Http\\Requests\\RequestStatus|null', $request->enum($key, RequestStatus::class));
    assertType('null', $request->enum('role', RequestRole::class));
    assertType('App\\Http\\Requests\\RequestStatus|null', $request->enum('avatar', RequestStatus::class));
}

function testEnums(AccessorRequest $request, string $key): void
{
    assertType('array<App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published>', $request->enums('statuses', RequestStatus::class));
    assertType('array<0, App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published>', $request->enums('status', RequestStatus::class));
    assertType('array<0, App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published>', $request->enums('name', RequestStatus::class));
    assertType('array{}', $request->enums('role', RequestRole::class));
    assertType('array{}', $request->enums('mode', RequestStatus::class));
    assertType('array<App\\Http\\Requests\\RequestStatus>', $request->enums('unknown', RequestStatus::class));
    assertType('array<App\\Http\\Requests\\RequestStatus>', $request->enums($key, RequestStatus::class));
}

function testArrayAndCollect(AccessorRequest $request, string $key): void
{
    assertType('array<string>', $request->array('tags'));
    assertType('array{age: float|int<min, 120>|numeric-string, city?: string, ...}', $request->array('profile'));
    assertType('array{non-empty-string}', $request->array('name'));
    assertType('array{}|array{string}', $request->array('nickname'));
    assertType('array', $request->array('unknown'));
    assertType('array', $request->array($key));
    assertType('array', $request->array('avatar'));
    assertType('array', $request->array(['name', 'mode']));
    assertType('Illuminate\\Support\\Collection<(int|string), string>', $request->collect('tags'));
    assertType('Illuminate\\Support\\Collection<0, non-empty-string>', $request->collect('name'));
    assertType('Illuminate\\Support\\Collection<0, string>', $request->collect('nickname'));
    assertType('Illuminate\\Support\\Collection', $request->collect('unknown'));
    assertType('Illuminate\\Support\\Collection', $request->collect('avatar'));
}

function testShapes(AccessorRequest $request, SafeReturnRequest $safeReturnRequest, string $key): void
{
    assertType(
        'array{name: non-empty-string, nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string, ...}, excluded?: mixed, unknown: mixed, ...}',
        $safeReturnRequest->all(),
    );
    assertType('array{name: non-empty-string, nickname?: string}', $safeReturnRequest->only(['name', 'nickname']));
    assertType('array{name: non-empty-string, nickname?: string}', $safeReturnRequest->only('name', 'nickname'));
    assertType('array{profile: array{email: non-empty-string}}', $safeReturnRequest->only(['profile.email']));
    assertType('array{unknown: mixed}', $safeReturnRequest->only(['unknown']));
    assertType('array', $safeReturnRequest->only([$key]));
    assertType(
        'array{nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string, ...}, excluded?: mixed, unknown: mixed, ...}',
        $safeReturnRequest->except(['name']),
    );
    assertType('array', $safeReturnRequest->except('profile.age'));
    assertType(
        'array{profile: array{email: non-empty-string, age?: float|int|numeric-string, ...}, excluded?: mixed, unknown: mixed, ...}',
        $safeReturnRequest->except('name', 'nickname'),
    );
    assertType('array', $safeReturnRequest->except([$key]));
    assertType('Illuminate\\Http\\UploadedFile', $request->all()['avatar']);
    assertType('array{avatar: Illuminate\\Http\\UploadedFile}', $request->only(['avatar']));
    assertType('true', $safeReturnRequest->has('name'));
    assertType('true', $safeReturnRequest->has('name', 'profile.email'));
    assertType('true', $safeReturnRequest->has(['name', 'profile.email']));
    assertType('bool', $safeReturnRequest->has('nickname'));
    assertType('bool', $safeReturnRequest->has('name', 'nickname'));
    assertType('bool', $safeReturnRequest->has('unknown.child'));
    assertType('bool', $safeReturnRequest->has($key));
    assertType('true', $safeReturnRequest->exists('name'));
    assertType('false', $safeReturnRequest->missing('name'));
    assertType('bool', $safeReturnRequest->missing('nickname'));
    assertType('true', $safeReturnRequest->safe()->has('name'));
    assertType('false', $safeReturnRequest->safe()->has('missing'));
    assertType('true', $safeReturnRequest->safe()->missing('missing'));
    assertType('bool', $safeReturnRequest->safe()->has('nickname'));
    assertType('array{name: non-empty-string}', $safeReturnRequest->safe()->only(['name']));
    assertType('array{name: non-empty-string}', $safeReturnRequest->safe()->only('name'));
    assertType(
        'array{nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string}, unknown: mixed}',
        $safeReturnRequest->safe()->except(['name']),
    );
    assertType('array', $safeReturnRequest->safe()->except(['profile.age']));
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->safe()->enum('status', RequestStatus::class));
    assertType('array<App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published>', $request->safe()->enums('statuses', RequestStatus::class));
    assertType('float', $request->safe()->float('ratio'));
    assertType('array{non-empty-string}', $request->safe()->array('name'));
    assertType('Illuminate\\Support\\Collection<0, non-empty-string>', $request->safe()->collect('name'));
    assertType('Illuminate\\Support\\Stringable', $request->safe()->string('name'));
    assertType('Illuminate\\Support\\Stringable', $request->string('name'));
}

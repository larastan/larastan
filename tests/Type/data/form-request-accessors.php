<?php

declare(strict_types=1);

namespace FormRequestAccessors;

use App\Http\Requests\AccessorRequest;
use App\Http\Requests\AdditionalRulesRequest;
use App\Http\Requests\FooRequest;
use App\Http\Requests\RequestRole;
use App\Http\Requests\RequestStatus;
use App\Http\Requests\SafeReturnRequest;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

function testInput(AccessorRequest $request, FormRequest $formRequest, string $key): void
{
    assertType('float|int<1, 5>|numeric-string', $request->input('count'));
    assertType('mixed', $request->input($key));
    assertType('mixed', $formRequest->input('name'));

    // Uploaded files are not part of input().
    assertType('mixed', $request->input());
}

function testInteger(
    AccessorRequest $request,
    FooRequest $fooRequest,
    AdditionalRulesRequest $additionalRulesRequest,
    string $key,
): void
{
    assertType('int', $request->integer($key));
    assertType('int', $request->integer('name'));
    assertType('int<0, 20>', $fooRequest->integer('limit'));
    assertType('int<1, 20>', $fooRequest->integer('limit', 5));
    assertType('int<min, -5>', $additionalRulesRequest->integer('numericLessThanValue'));
}

function testBoolean(AccessorRequest $request, FooRequest $fooRequest, string $key): void
{
    assertType('false', $request->boolean('marketing'));
    assertType('bool', $request->boolean($key));
    assertType('bool', $fooRequest->boolean('newsletter'));
    assertType('true', $fooRequest->boolean('newsletter', true));
}

function testValidatedInput(AccessorRequest $request, string $key): void
{
    assertType('mixed', $request->safe()->input($key));
    assertType('mixed', $request->safe()['name']);
}

function testOverrides(AccessorRequest|OverriddenInputRequest $either): void
{
    assertType('mixed', $either->input('name'));
}

function testUnions(AccessorRequest|SafeReturnRequest $either): void
{
    assertType('mixed', $either->input('count'));
}

function testFloat(AccessorRequest $request): void
{
    assertType('0.0', $request->float('mode'));
}

function testEnum(AccessorRequest $request): void
{
    assertType('null', $request->enum('role', RequestRole::class));
}

function testEnums(AccessorRequest $request): void
{
    assertType('array{}', $request->enums('mode', RequestStatus::class));
    assertType('array<App\\Http\\Requests\\RequestStatus>', $request->enums('unknown', RequestStatus::class));
}

function testArrayAndCollect(AccessorRequest $request): void
{
    assertType('array<string>', $request->array('tags'));
    assertType('Illuminate\\Support\\Collection<0, string>', $request->collect('nickname'));
    assertType('Illuminate\\Support\\Collection', $request->collect('unknown'));
}

function testShapes(AccessorRequest $request, SafeReturnRequest $safeReturnRequest, string $key): void
{
    assertType('array', $safeReturnRequest->only([$key]));
    assertType('array', $safeReturnRequest->except('profile.age'));
    assertType('array', $safeReturnRequest->except([$key]));
    assertType('Illuminate\\Http\\UploadedFile', $request->all()['avatar']);
    assertType('array{avatar: Illuminate\\Http\\UploadedFile}', $request->only(['avatar']));
    assertType('true', $safeReturnRequest->has('name'));
    assertType('bool', $safeReturnRequest->has($key));
    assertType('true', $safeReturnRequest->exists('name'));
    assertType('false', $safeReturnRequest->missing('name'));
    assertType(
        'array{nickname?: string, profile: array{email: non-empty-string, age?: float|int|numeric-string}, unknown: mixed}',
        $safeReturnRequest->safe()->except(['name']),
    );
    assertType('App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published', $request->safe()->enum('status', RequestStatus::class));
}

class OverriddenInputRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }

    public function input($key = null, $default = null): mixed
    {
        return parent::input($key, $default);
    }

    public function integer($key, $default = 0): int
    {
        return parent::integer($key, $default);
    }
}

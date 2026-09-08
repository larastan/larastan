<?php

namespace FormRequestUnknownKey;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\ValidatedInput;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'string',
            'profile' => 'nullable|array:name',
            'profile.name' => 'string',
            'users' => 'array',
            'users.*.email' => 'string',
            'removed' => 'exclude',
            'conditional' => 'exclude_if:email,foo|string',
            'opaque' => '',
            'open' => 'array',
            'numeric' => 'array:0,1',
        ];
    }
}

class InheritedRequest extends StoreRequest
{
}

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => 'string'];
    }
}

class UnknownRequest extends FormRequest
{
    public function rules(): array
    {
        return config('validation.rules');
    }
}

class OverriddenRequest extends FormRequest
{
    public function validated($key = null, $default = null): mixed
    {
        return $default;
    }

    public function safe(?array $keys = null): ValidatedInput|array
    {
        return [];
    }
}

function selectors(StoreRequest $request, string $dynamic, bool $condition): void
{
    $request->validated('emali');
    $request->validated('emali', false);
    $request->validated(default: fn () => false, key: 'emali');
    $request->safe(keys: ['email', 'emali', 'emali', 'profile.emali']);
    $request->safe()->only(['emali']);
    $request->safe(null)->only('emali', 'profile.emali');
    $request->validated('profile.name');
    $request->validated('profile.emali');
    $request->validated('email.0');
    $request->validated('users.0.email');
    $request->validated('users.0.emali');
    $request->validated('removed');
    $request->validated('conditional');
    $request->validated('opaque.anything');
    $request->validated('open.anything');
    $request->validated('numeric.0');
    $request->validated(1);
    $request->safe(['numeric.0']);
    $request->validated('00');
    $request->validated('');
    $request->validated('profile..name');

    $key = 'emali';
    $request->validated($key);
    $request->validated($dynamic);
    $request->validated($condition ? 'emali' : 'email');
    $request->safe(['emali', $dynamic]);
    $request->safe()->only('emali', $dynamic);
    $request->safe($condition ? ['emali'] : []);
    $request->validated(...['emali']);
    $request->safe()->only(...['emali']);
    $request->validated();
    $request->validated(key: null);
    $request->validated(default: false);
    $request->safe([]);
    $request->safe()->only([]);
    $request->validated(['profile', 'emali']);
    $request->validated('users.*.emali');
    $request->validated('users.{first}.emali');
    $request->validated('users.{last}.emali');
    $request->validated('users.\\*.emali');
    $request->validated('users.\\{first}.emali');
    $request->validated('users.\\{last}.emali');
    $request->validated(...);
    $method = 'validated';
    $request->$method('emali');
    $request->safe()->merge(['emali' => 'value'])->only(['emali']);
    $safe = $request->safe();
    $safe->only(['emali']);
    $request->safe()->except(['emali']);
    $request->input('emali');
}

function nullable(?StoreRequest $request): void
{
    $request?->validated('emali');
    $request?->safe()?->only(['emali']);
}

function inherited(InheritedRequest $request): void
{
    $request->validated('emali');
}

function overridden(OverriddenRequest $request): void
{
    $request->validated('emali');
    $request->safe(['emali']);
    $request->safe()->only(['emali']);
}

function unknown(UnknownRequest $request): void
{
    $request->validated('emali');
}

function unions(StoreRequest|UpdateRequest $request, StoreRequest|UnknownRequest $unknown, StoreRequest|OverriddenRequest $override): void
{
    $request->validated('email', false);
    $request->safe()->only(['email']);
    $request->validated('absent');
    $unknown->validated('absent');
    $override->validated('absent');
}

/**
 * @template T of StoreRequest
 * @param T $request
 */
function generic(StoreRequest $request): void
{
    $request->validated('emali');
}

class NumericRootRequest extends FormRequest
{
    public function rules(): array
    {
        return ['0' => 'string'];
    }
}

class OpenRootRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ...config('validation.rules'),
            'profile' => 'array:name',
            'profile.name' => 'string',
            'upload' => 'file',
        ];
    }
}

function openShapes(NumericRootRequest $numeric, OpenRootRequest $open): void
{
    $numeric->validated(0);
    $numeric->validated('emali');
    $numeric->safe(['0']);
    $open->validated('emali');
    $open->validated('profile.emali');
    $open->validated('upload.unknown');
}

/** @param __benevolent<StoreRequest|UpdateRequest> $request */
function benevolent($request): void
{
    $request->validated('absent');
}

interface Marker
{
}

function intersection(StoreRequest&Marker $request): void
{
    $request->validated('email');
}

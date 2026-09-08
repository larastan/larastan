<?php

namespace FormRequestRulePairs;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LiteralRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'bail|required|nullable|email',
            'absent' => ['required', 'missing'],
            'triple' => 'required|missing|nullable',
            'nullable' => 'nullable|string',
            'present' => 'present|nullable',
            'conditional' => 'required|nullable|required_if:other,true',
            'sometimes' => 'sometimes|required|nullable',
            'excluded' => 'exclude|required|missing',
            'excludedIf' => 'required|nullable|exclude_if:other,true',
            'custom' => 'required|nullable|custom_rule',
            'object' => ['required', 'nullable', Rule::in(['x'])],
            'closure' => ['required', 'nullable', fn () => null],
            'nested' => [['required', 'nullable']],
            'pipeInArray' => ['required|nullable'],
            'parameters' => 'in:required,nullable',
            'caseSensitive' => 'REQUIRED|nullable',
            'arrayKeys' => 'required|nullable|required_array_keys:a',
            'parent' => 'required|nullable|array',
            'parent.child' => 'string',
            'escaped\\.key' => 'required|nullable',
            'other.child' => 'required|missing',
            'regex' => ['required', 'nullable', 'regex:/required|nullable/'],
            'same' => 'required|nullable',
            'same' => 'string',
        ];
    }
}

class WildcardRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => 'required|nullable', 'users.*.name' => 'string'];
    }
}

class DynamicRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = ['email' => 'required|nullable'];
        $callback = fn () => ['ignored' => 'required|nullable'];
        $closure = function () { return ['ignored' => 'required|missing']; };
        if ($this->boolean('branch')) {
            return $rules;
        }

        $rules['email'] = $this->boolean('nullable') ? 'required|nullable' : 'string';
        $rules['unknown'] = ['required', 'nullable', config('validation.rule')];
        return $rules;
    }
}

class OpenRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => 'required|nullable', ...config('validation.rules')];
    }
}

class OptionalRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [];
        if ($this->boolean('include')) {
            $rules['email'] = 'required|nullable';
        }
        return $rules;
    }
}

class OtherObject
{
    public function rules(): array
    {
        return ['email' => 'required|nullable'];
    }
}

class NumericSiblingRequest extends FormRequest
{
    public function rules(): array
    {
        return [0 => 'required|nullable', 'email' => 'required|nullable'];
    }
}

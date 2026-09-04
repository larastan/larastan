<?php

declare(strict_types=1);

namespace FormRequestFeatureEnabled;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

class EnabledRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }
}

function acceptsString(string $value): void
{
}

/** @param In<array{'enabled'}> $rule */
function acceptsIn(In $rule): void
{
}

function test(EnabledRequest $request): void
{
    acceptsString($request->name);
    acceptsIn(Rule::in(['enabled']));
}

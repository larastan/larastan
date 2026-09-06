<?php

declare(strict_types=1);

namespace FormRequestFeatureDisabled;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\ArrayRule;
use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Numeric;

use function PHPStan\Testing\assertType;

class DisabledRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }
}

function acceptsRules(
    ArrayRule $arrayRule,
    Date $date,
    Enum $enum,
    In $in,
    Numeric $numeric,
): void {
}

function test(DisabledRequest $request): void
{
    assertType('array<string, mixed>', $request->validated());
    assertType('mixed', $request->validated('name'));
    assertType('Illuminate\\Support\\ValidatedInput', $request->safe());
    assertType('array{name: mixed}', $request->safe(['name']));

    $request->name = 1;
}

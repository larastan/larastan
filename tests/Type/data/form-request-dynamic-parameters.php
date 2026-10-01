<?php

declare(strict_types=1);

namespace FormRequestDynamicParameters;

use Illuminate\Foundation\Http\FormRequest;


use function PHPStan\Testing\assertType;

function testDynamicParameters(DynamicParametersRequest $request): void
{
    assertType('non-empty-string', $request->email);
    assertType('float|int|non-empty-string', $request->formattedDate);
    assertType('float|int|non-empty-string', $request->pattern);
    assertType('mixed', $request->excludedValue);
    assertType('mixed', $request->unknownRule);
}

class DynamicParametersRequest extends FormRequest
{
    public function rules(): array
    {
        $parameter = (string) config('app.rule.parameter');

        return [
            'title' => ['required', 'string', 'min:' . $parameter, 'max:' . $parameter],
            'email' => ['required', 'email:' . $parameter],
            'formattedDate' => ['required', 'date_format:' . $parameter],
            'decimal' => ['required', 'decimal:' . $parameter],
            'pattern' => ['required', "regex:{$parameter}"],
            'choice' => ['required', 'string', 'in:' . $parameter],
            'record' => ['required', 'array:' . $parameter],
            'record.name' => ['string'],
            'values' => ['required', 'list:' . $parameter],
            'excludedValue' => ['exclude_if:flag,' . $parameter, 'required', 'string'],
            'integerValue' => ['required', 'integer:' . $parameter],
            'numericValue' => ['required', 'numeric:' . $parameter],
            'booleanValue' => ['required', 'boolean:' . $parameter],
            'unknownRule' => ['required', 'string', $parameter . ':anything'],
        ];
    }

    /** @return array{'required', 'string', string} */
    private function helperRules(): array
    {
        return ['required', 'string', 'max:' . config('app.rule.maximum')];
    }
}

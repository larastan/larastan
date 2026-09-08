<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Rules\NoTagsRule;
use App\Rules\URLRule;
use Illuminate\Foundation\Http\FormRequest;

final class DynamicParametersRequest extends FormRequest
{
    public function rules(): array
    {
        $parameter = (string) config('app.rule.parameter');
        $variableRules = ['required', 'string', 'max:' . $parameter];
        $ruleName = 'string';

        return [
            'title' => ['required', 'string', 'min:' . $parameter, 'max:' . $parameter],
            'splitName' => ['required', $ruleName . ':' . $parameter],
            'interpolatedName' => ['required', "{$ruleName}:{$parameter}"],
            'email' => ['required', 'email:' . $parameter],
            'boundedEmail' => ['required', 'email', 'max:' . config('app.validation.email.max_length')],
            'formattedDate' => ['required', 'date_format:' . $parameter],
            'decimal' => ['required', 'decimal:' . $parameter],
            'pattern' => ['required', "regex:{$parameter}"],
            'choice' => ['required', 'string', 'in:' . $parameter],
            'limitedChoice' => ['required', 'string', 'in:alpha,beta', 'in:' . $parameter],
            'record' => ['required', 'array:' . $parameter],
            'record.name' => ['string'],
            'limitedRecord' => ['required', 'array:name,other', 'array:' . $parameter],
            'limitedRecord.name' => ['string'],
            'values' => ['required', 'list:' . $parameter],
            'nullableValue' => ['present', 'string', 'nullable:' . $parameter],
            'optionalValue' => ['required', 'string', 'sometimes:' . $parameter],
            'requiredValue' => ['required', 'string', 'required_if:flag,' . $parameter],
            'excludedValue' => ['exclude_if:flag,' . $parameter, 'required', 'string'],
            'integerValue' => ['required', 'integer:' . $parameter],
            'numericValue' => ['required', 'numeric:' . $parameter],
            'booleanValue' => ['required', 'boolean:' . $parameter],
            'boundedValue' => ['required', 'string', 'min:2', 'min:' . $parameter, 'max:' . $parameter],
            'unknownRule' => ['required', 'string', $parameter . ':anything'],
            'pipeParameter' => ['required', 'string', 'in:' . $parameter . '|exclude'],
            'wholeString' => 'required|string|min:' . $parameter,
            'variableRules' => $variableRules,
            'helperRules' => $this->helperRules(),
            'work' => ['required', 'array', 'min:1', 'max:10'],
            'work.*.sourceUrl' => ['required', new URLRule()],
            'work.*.originalTitle' => [
                'required',
                'string',
                'min:' . config('document.rules.name.min_length'),
                'max:' . config('document.rules.name.max_length'),
                new NoTagsRule(),
            ],
            'work.*.removalRequestReason' => ['required', 'string', 'max:500', new NoTagsRule()],
        ];
    }

    /** @return array{'required', 'string', string} */
    private function helperRules(): array
    {
        return ['required', 'string', 'max:' . config('app.rule.maximum')];
    }
}

<?php

declare(strict_types=1);

namespace FormRequestDynamicParameters;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

final class URLRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            $fail('The URL is invalid.');
        }
    }
}

final class NoTagsRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && strip_tags($value) !== $value) {
            $fail('The value must not contain tags.');
        }
    }
}

final class WorkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
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
}

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
        ];
    }

    /** @return array{'required', 'string', string} */
    private function helperRules(): array
    {
        return ['required', 'string', 'max:' . config('app.rule.maximum')];
    }
}

function testDynamicParameters(WorkRequest $work, DynamicParametersRequest $request): void
{
    assertType(
        'array<array{sourceUrl: mixed, originalTitle: non-empty-string, removalRequestReason: non-empty-string, ...}>',
        $work->work,
    );
    assertType('non-empty-string', $request->validated('title'));
    assertType('non-empty-string', $request->splitName);
    assertType('non-empty-string', $request->interpolatedName);
    assertType('non-empty-string', $request->email);
    assertType('non-empty-string', $request->boundedEmail);
    assertType('non-empty-string', $request->validated('boundedEmail'));
    assertType('non-empty-string', $request->formattedDate);
    assertType('float|int|numeric-string', $request->decimal);
    assertType('float|int|non-empty-string', $request->pattern);
    assertType('non-empty-string', $request->choice);
    assertType("'alpha'|'beta'", $request->limitedChoice);
    assertType('array{name?: string, ...}', $request->validated('record'));
    assertType('array{name?: string, other?: mixed}', $request->validated('limitedRecord'));
    assertType('list', $request->values);
    assertType('string|null', $request->nullableValue);
    assertType('non-empty-string|null', $request->optionalValue);
    assertType('non-empty-string', $request->requiredValue);
    assertType('mixed', $request->excludedValue);
    assertType('non-empty-string|null', $request->validated('excludedValue'));
    assertType('float|int|numeric-string', $request->integerValue);
    assertType('float|int|numeric-string', $request->numericValue);
    assertType("0|1|'0'|'1'|bool", $request->booleanValue);
    assertType('non-empty-string', $request->boundedValue);
    assertType('mixed', $request->unknownRule);
    assertType('non-empty-string', $request->pipeParameter);
    assertType('mixed', $request->wholeString);
    assertType('mixed', $request->variableRules);
    assertType('mixed', $request->helperRules);
}

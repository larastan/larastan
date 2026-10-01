<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

enum RequestStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}

enum RequestPriority: int
{
    case Low = 1;
    case High = 2;
}

enum RequestRole
{
    case Admin;
    case User;
}

/** @implements Arrayable<int, string> */
final class RequestValues implements Arrayable
{
    /** @return array{'draft', 'published'} */
    public function toArray(): array
    {
        return ['draft', 'published'];
    }
}

class FooRequest extends FormRequest
{
    public function rules(): array
    {
        $condition = config('app.rule.condition');
        $limit = config('app.rule.limit');
        $rule = config('app.rule.rule');
        $mixedValue = config('app.rule.value');
        $stateRule = Rule::in(['draft', 'published']);
        $numericRule = Rule::numeric()->integer()->min(1)->max(10);

        return [
            'name' => 'required|string',
            'customRule' => ['required', 'string', static function (string $attribute, mixed $value, \Closure $fail): void {
            }],
            'age' => ['required', 'integer', 'min:' . $limit, $rule],
            'newsletter' => 'sometimes|accepted',
            'type' => 'required|in:date,rating',
            'rating' => 'required|integer|in:0,1',
            'nickname' => 'sometimes|string|in:john-d,dash',
            'options.display.mode' => 'required|string',
            'tags.*' => 'string',
            'properties' => ['sometimes', 'array'],
            'properties.*' => ['sometimes'],
            'users.*.email' => 'required|email',
            'users.*.age' => 'sometimes|integer',
            'users.*.addresses.*.city' => 'required|string',
            'users.*.address' => 'sometimes|array',
            'users.*.address.city' => 'required|string',
            'accounts' => 'nullable|array',
            'accounts.*.id' => 'required|integer',
            'version' => 'required|string',
            'version.0' => 'string',
            'flags' => 'required|array',
            'flags.*' => 'string',
            'flags.enabled' => 'boolean',
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'url.fragment' => ['present', 'nullable', 'string'],
            'url.domain' => ['required', 'string', $rule],
            'url.port' => ['required', $rule],
            'dynamicRules' => [...$this->defaultRules()],
            'state' => ['required', 'string', $stateRule],
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'stringStatus' => ['required', 'string', Rule::enum(RequestStatus::class)],
            'priority' => ['required', Rule::enum(RequestPriority::class)],
            'role' => ['required', Rule::enum(RequestRole::class)],
            'arrayableState' => ['required', 'string', Rule::in(new RequestValues())],
            'primitiveState' => ['required', 'string', Rule::in([1, 1.5, true, false, null])],
            'objectState' => ['required', 'string', Rule::in([RequestRole::Admin])],
            'escapedState' => ['required', 'string', Rule::in(['a\\'])],
            'untypedState' => ['required', Rule::in(['draft', 'published'])],
            'uncertainState' => ['required', 'string', Rule::in([1, $mixedValue])],
            'arrayIn' => 'required|array|in:draft,published',
            'listIn' => 'required|list|in:draft,published',
            'arrayRuleIn' => ['required', 'array', Rule::in(['draft', 'published'])],
            'listRuleIn' => ['required', 'list', Rule::in(['draft', 'published'])],
            'numericArrayIn' => 'required|array|in:1,2',
            'unknownArrayIn' => ['required', 'array', Rule::in([1, $mixedValue])],
            'airports' => ['required', 'array'],
            'airports.*' => Rule::in(['NYC', 'LIT']),
            'payload' => ['required', Rule::array(['name', 'count'])],
            'payload.name' => 'required|string',
            'numericValue' => ['required', Rule::numeric()],
            'integerValue' => ['required', $numericRule],
            'extension' => ['sometimes', 'nullable', 'string', 'max:4', 'alpha_num'],
            'stringPriority' => ['required', 'string', Rule::enum(RequestPriority::class)],
            'whenValue' => ['required', Rule::when($condition, 'array', 'string')],
            'unlessValue' => [
                'required',
                Rule::unless($condition, static fn (): string => 'array', static fn (): string => 'string'),
            ],
            'exactWhenValue' => [
                'required',
                Rule::when(defaultRules: 'array', rules: 'string', condition: true),
            ],
            'conditionallyExcluded' => ['required', Rule::when($condition, 'exclude', 'string')],
            'alwaysRequired' => [Rule::requiredIf(true), 'string'],
            'maybeRequired' => [Rule::requiredIf(static fn (): bool => true), 'string'],
            'neverExcluded' => ['required', Rule::excludeIf(false), 'string'],
            'maybeExcluded' => ['required', Rule::excludeIf(static fn (): bool => false), 'string'],
            'alwaysExcluded' => ['required', Rule::excludeIf(true), 'string'],
        ];
    }

    /** @return array<string, string> */
    private function defaultRules(): array
    {
        return ['fallback' => 'required|string'];
    }
}

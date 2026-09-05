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
        $limit = config('app.rule.limit');
        $rule = config('app.rule.rule');
        $mixedValue = config('app.rule.value');
        $stateRule = Rule::in(['draft', 'published']);
        $numericRule = Rule::numeric()->integer()->min(1)->max(10);

        return [
            'name' => 'required|string',
            'optionalName' => 'string',
            'age' => ['required', 'integer', 'min:' . $limit, $rule],
            'newsletter' => 'sometimes|accepted',
            'type' => 'required|in:date,rating',
            'rating' => 'required|integer|in:0,1',
            'nickname' => 'sometimes|string|in:john-d,dash',
            'price' => 'required|numeric',
            'sortOrder' => 'sometimes|prohibited_if:sortBy,rating|required_unless:sortBy,rating|in:desc,asc',
            'settings' => 'required|array',
            'author.name' => 'required|string',
            'author.surname' => 'nullable|string',
            'author.nickname' => 'sometimes|string',
            'author.thing' => 'required',
            'options.display.mode' => 'required|string',
            'prefs.theme' => 'string',
            'positions.0.x' => 'integer',
            'tags.*' => 'string',
            'scores.*' => 'nullable|integer',
            'properties' => ['sometimes', 'array'],
            'properties.*' => ['sometimes'],
            'listProperties' => ['sometimes', 'list'],
            'listProperties.*' => ['sometimes', 'string'],
            'users.*.email' => 'required|email',
            'users.*.age' => 'sometimes|integer',
            'users.*.addresses.*.city' => 'required|string',
            'guests' => 'required|array',
            'guests.*.name' => 'required|string',
            'users.*.address' => 'sometimes|array',
            'users.*.address.city' => 'required|string',
            'accounts' => 'nullable|array',
            'accounts.*.id' => 'required|integer',
            'recursive.recursive.recursive' => 'required|string',
            'nestedGroups' => 'required|array',
            'nestedGroups.*.members' => 'required|array',
            'nestedGroups.*.members.*.name' => 'required|string',
            'copied' => 'required',
            'copied.name' => 'required|string',
            'nullableProfile' => 'nullable|array',
            'nullableProfile.name' => 'required|string',
            'conflicted' => 'required|string',
            'conflicted.*.x' => 'integer',
            'version' => 'required|string',
            'version.0' => 'string',
            'metadata.name' => 'required|string',
            'metadata.items.0' => 'string',
            'shipping.*.origin' => 'nullable|array',
            'shipping.*.origin.zip' => 'required|string',
            'v1\.0' => 'required|string',
            'flags' => 'required|array',
            'flags.*' => 'string',
            'flags.enabled' => 'boolean',
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'url.fragment' => ['present', 'nullable', 'string'],
            'url.domain' => ['required', 'string', $rule],
            'url.path' => ['present', 'nullable', 'string'],
            'url.port' => ['required', $rule],
            ...$this->defaultRules(),
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
            'arrayablePayload' => ['required', Rule::array(new RequestValues())],
            'arrayablePayload.draft' => 'required|string',
            'sometimesPayload' => ['sometimes', 'required', Rule::array(['name'])],
            'commaPayload' => ['required', Rule::array(['first,last'])],
            'numericValue' => ['required', Rule::numeric()],
            'integerValue' => ['required', $numericRule],
            'extension' => ['sometimes', 'nullable', 'string', 'max:4', 'alpha_num'],
            'reversedExtension' => ['sometimes', 'nullable', 'alpha_num', 'max:4', 'string'],
        ];
    }

    /** @return array<string, string> */
    private function defaultRules(): array
    {
        return ['fallback' => 'required|string'];
    }
}

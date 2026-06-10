<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FooRequest extends FormRequest
{
    public function rules(): array
    {
        $limit = config('app.rule.limit');
        $rule = config('app.rule.rule');

        return [
            'name' => 'required|string',
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
            'users.*.email' => 'required|email',
            'users.*.age' => 'sometimes|integer',
            'users.*.addresses.*.city' => 'required|string',
            'guests' => 'required|array',
            'guests.*.name' => 'required|string',
            'users.*.address' => 'sometimes|array',
            'users.*.address.city' => 'required|string',
            'accounts' => 'nullable|array',
            'accounts.*.id' => 'required|integer',
            'conflicted' => 'required|string',
            'conflicted.*.x' => 'integer',
            'shipping.*.origin' => 'nullable|array',
            'shipping.*.origin.zip' => 'required|string',
            'v1\.0' => 'required|string',
            'flags.*' => 'string',
            'flags.enabled' => 'boolean',
            'limit' => ['sometimes', 'integer', 'min:1', 'max:20'],
            'url.fragment' => ['present', 'nullable', 'string'],
            'url.domain' => ['required', 'string', $rule],
            'url.path' => ['present', 'nullable', 'string'],
            'url.port' => ['required', $rule],
            ...$this->defaultRules(),
            'dynamicRules' => [...$this->defaultRules()],
        ];
    }

    /** @return array<string, string> */
    private function defaultRules(): array
    {
        return ['fallback' => 'required|string'];
    }
}

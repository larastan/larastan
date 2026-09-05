<?php

declare(strict_types=1);

namespace FormRequest;

use App\Http\Requests\FooRequest;
use App\Http\Requests\RequestPriority;
use App\Http\Requests\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

const GLOBAL_RULE = 'integer';

class VariableRulesRequest extends FormRequest
{
    private const MAX = 20;

    private const RULE = 'string';

    public function rules(): array
    {
        $localRule = 'integer';

        $rules = [
            'title' => 'required|' . self::RULE,
            'quantity' => ['required', $localRule],
            'maximum' => ['required', 'integer', 'max:' . self::MAX],
            'global' => 'required|' . GLOBAL_RULE,
        ];

        return $rules;
    }
}

class ConditionalRulesRequest extends FormRequest
{
    public function rules(): array
    {
        $condition = config('app.rule.condition');

        return [
            'possiblyExcluded' => 'exclude_if:kind,skip|integer',
            'conditionallyAccepted' => 'accepted_if:kind,accept',
            'conditionallyDeclined' => 'declined_if:kind,decline',
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
            'alwaysRequiredUnless' => [Rule::requiredUnless(false), 'string'],
            'maybeRequired' => [Rule::requiredIf(static fn (): bool => true), 'string'],
            'neverExcluded' => ['required', Rule::excludeIf(false), 'string'],
            'neverExcludedUnless' => ['required', Rule::excludeUnless(true), 'string'],
            'maybeExcluded' => ['required', Rule::excludeIf(static fn (): bool => false), 'string'],
            'alwaysExcluded' => ['required', Rule::excludeIf(true), 'string'],
            'alwaysExcludedUnless' => ['required', Rule::excludeUnless(false), 'string'],
        ];
    }
}

class SafeReturnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'nickname' => 'string',
            'profile.email' => 'required|string',
            'profile.age' => 'integer',
            'excluded' => 'exclude',
            'unknown' => 'required',
        ];
    }
}

class OverriddenSafeRequest extends SafeReturnRequest
{
    /** @return array{custom: string} */
    public function safe(?array $keys = null): array
    {
        return ['custom' => 'value'];
    }
}

function test(
    FormRequest $request,
    FooRequest $fooRequest,
    VariableRulesRequest $variableRulesRequest,
    ConditionalRulesRequest $conditionalRulesRequest,
    SafeReturnRequest $safeReturnRequest,
    OverriddenSafeRequest $overriddenSafeRequest,
): void
{
    assertType('Illuminate\Support\ValidatedInput', $request->safe());
    assertType('array<string, mixed>', $request->safe(['key']));
    assertType('array<string, mixed>', $request->validated());

    assertType(
        'Illuminate\\Support\\ValidatedInput<array{name: string, nickname?: string, profile: array{email: string, age?: (int|numeric-string)}, unknown: mixed}>',
        $safeReturnRequest->safe(),
    );
    assertType('array{name: string, nickname?: string}', $safeReturnRequest->safe(['name', 'nickname']));
    assertType('array{profile: array{email: string}}', $safeReturnRequest->safe(['profile.email']));
    assertType(
        'array{profile?: array{age?: (int|numeric-string)}}',
        $safeReturnRequest->safe(['profile.age']),
    );
    assertType(
        'array{profile: array{email: string, age?: (int|numeric-string)}}',
        $safeReturnRequest->safe(['profile.email', 'profile.age']),
    );
    assertType('array{}', $safeReturnRequest->safe(['missing']));
    assertType('array<string, mixed>', $safeReturnRequest->safe(['unknown.child']));
    assertType('array', $safeReturnRequest->safe()->all());
    assertType('mixed', $safeReturnRequest->safe()->input('name'));
    assertType('mixed', $safeReturnRequest->safe()['name']);
    assertType('array{custom: string}', $overriddenSafeRequest->safe());

    assertType('string', $fooRequest->name);
    assertType('string|null', $fooRequest->optionalName);
    assertType('mixed', $fooRequest->age);
    assertType("1|'1'|'on'|'true'|'yes'|true|null", $fooRequest->newsletter);
    assertType("'date'|'rating'", $fooRequest->type);
    assertType('(int|numeric-string)', $fooRequest->rating);
    assertType("'dash'|'john-d'|null", $fooRequest->nickname);
    assertType('(float|int|numeric-string)', $fooRequest->price);
    assertType("'asc'|'desc'|null", $fooRequest->sortOrder);
    assertType('array', $fooRequest->settings);
    assertType('array{name: string, surname?: string|null, nickname?: string, thing: mixed, ...}', $fooRequest->author);
    assertType('array{display: array{mode: string, ...}, ...}', $fooRequest->options);
    assertType('mixed', $fooRequest->prefs);
    assertType('mixed', $fooRequest->positions);
    assertType('mixed', $fooRequest->tags);
    assertType('mixed', $fooRequest->scores);
    assertType('array<mixed>|null', $fooRequest->properties);
    assertType('list<mixed>|null', $fooRequest->listProperties);
    assertType('mixed', $fooRequest->users);
    assertType('array<array{name: string, ...}>', $fooRequest->guests);
    assertType('array<array{id: (int|numeric-string), ...}>|null', $fooRequest->accounts);
    assertType('string', $fooRequest->conflicted);
    assertType('string', $fooRequest->version);
    assertType('array{name: string, items?: mixed, ...}', $fooRequest->metadata);
    assertType('mixed', $fooRequest->shipping);
    assertType('string', $fooRequest->{'v1.0'});
    assertType('mixed', $fooRequest->flags);
    assertType('mixed', $fooRequest->{'author.name'});
    assertType('int|numeric-string|null', $fooRequest->limit);
    assertType('array{fragment: string|null, domain?: mixed, path: string|null, port?: mixed, ...}', $fooRequest->url);
    assertType('mixed', $fooRequest->fallback);
    assertType('mixed', $fooRequest->dynamicRules);
    assertType('string', $variableRulesRequest->title);
    assertType('(int|numeric-string)', $variableRulesRequest->quantity);
    assertType('(int|numeric-string)', $variableRulesRequest->maximum);
    assertType('(int|numeric-string)', $variableRulesRequest->global);
    assertType('mixed', $conditionalRulesRequest->possiblyExcluded);
    assertType('mixed', $conditionalRulesRequest->conditionallyAccepted);
    assertType('mixed', $conditionalRulesRequest->conditionallyDeclined);
    assertType('array|string', $conditionalRulesRequest->whenValue);
    assertType('array|string', $conditionalRulesRequest->unlessValue);
    assertType('string', $conditionalRulesRequest->exactWhenValue);
    assertType('mixed', $conditionalRulesRequest->conditionallyExcluded);
    assertType('string', $conditionalRulesRequest->alwaysRequired);
    assertType('string', $conditionalRulesRequest->alwaysRequiredUnless);
    assertType('string|null', $conditionalRulesRequest->maybeRequired);
    assertType('string', $conditionalRulesRequest->neverExcluded);
    assertType('string', $conditionalRulesRequest->neverExcludedUnless);
    assertType('mixed', $conditionalRulesRequest->maybeExcluded);
    assertType('mixed', $conditionalRulesRequest->alwaysExcluded);
    assertType('mixed', $conditionalRulesRequest->alwaysExcludedUnless);
    assertType("'draft'|'published'", $fooRequest->state);
    assertType("'draft'|'published'", $fooRequest->status);
    assertType("'draft'|'published'", $fooRequest->stringStatus);
    assertType("1|2|'1'|'2'", $fooRequest->priority);
    assertType('App\\Http\\Requests\\RequestRole::Admin|App\\Http\\Requests\\RequestRole::User', $fooRequest->role);
    assertType(
        'App\\Http\\Requests\\RequestStatus::Draft|App\\Http\\Requests\\RequestStatus::Published',
        RequestStatus::from($fooRequest->status),
    );
    assertType(
        'App\\Http\\Requests\\RequestPriority::High|App\\Http\\Requests\\RequestPriority::Low',
        RequestPriority::from($fooRequest->priority),
    );
    assertType("'draft'|'published'", $fooRequest->arrayableState);
    assertType("''|numeric-string", $fooRequest->primitiveState);
    assertType("'Admin'", $fooRequest->objectState);
    assertType('string', $fooRequest->escapedState);
    assertType("'draft'|'published'", $fooRequest->untypedState);
    assertType('string', $fooRequest->uncertainState);
    assertType("array<'draft'|'published'>", $fooRequest->arrayIn);
    assertType("list<'draft'|'published'>", $fooRequest->listIn);
    assertType("array<'draft'|'published'>", $fooRequest->arrayRuleIn);
    assertType("list<'draft'|'published'>", $fooRequest->listRuleIn);
    assertType('array', $fooRequest->numericArrayIn);
    assertType('array', $fooRequest->unknownArrayIn);
    assertType("array<'LIT'|'NYC'>", $fooRequest->airports);
    assertType('array{name: string, count?: mixed}', $fooRequest->payload);
    assertType('array{draft: string, published?: mixed}', $fooRequest->arrayablePayload);
    assertType('array{name?: mixed}|null', $fooRequest->sometimesPayload);
    assertType('array{first?: mixed, last?: mixed}', $fooRequest->commaPayload);
    assertType('(float|int|numeric-string)', $fooRequest->numericValue);
    assertType('(int|numeric-string)', $fooRequest->integerValue);
    assertType('string|null', $fooRequest->extension);
    assertType('string|null', $fooRequest->reversedExtension);
}

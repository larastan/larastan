<?php

declare(strict_types=1);

namespace FormRequest;

use Illuminate\Validation\Rule;
use App\Http\Requests\FooRequest;
use App\Http\Requests\SafeReturnRequest;
use App\Http\Requests\TraitRules\StringRequest;
use App\ValueObjects\InvokableDefault;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

/**
 * @param array{0?: 'name'} $optionalSafeKeys
 */
function test(
    FormRequest $request,
    FooRequest $fooRequest,
    SafeReturnRequest $safeReturnRequest,
    array $optionalSafeKeys,
): void
{
    assertType('Illuminate\Support\ValidatedInput', $request->safe());
    assertType('array<string, mixed>', $request->validated());

    assertType(
        'array{profile: array{email: non-empty-string, age?: float|int|numeric-string}}',
        $safeReturnRequest->safe(['profile.email', 'profile.age']),
    );
    assertType('array<string, mixed>', $safeReturnRequest->safe($optionalSafeKeys));

    assertType('non-empty-string', $fooRequest->version);
    assertType('non-empty-string', $fooRequest->customRule);
    assertType('array{fragment: string|null, domain?: mixed, port?: mixed, ...}', $fooRequest->url);
    assertType('mixed', $fooRequest->dynamicRules);
    assertType('array|non-empty-string', $fooRequest->whenValue);
    assertType('array|non-empty-string', $fooRequest->unlessValue);
    assertType('non-empty-string', $fooRequest->exactWhenValue);
    assertType('mixed', $fooRequest->conditionallyExcluded);
    assertType('non-empty-string', $fooRequest->alwaysRequired);
    assertType('string|null', $fooRequest->maybeRequired);
    assertType('non-empty-string', $fooRequest->neverExcluded);
    assertType('mixed', $fooRequest->maybeExcluded);
    assertType('mixed', $fooRequest->alwaysExcluded);
    assertType("'draft'|'published'", $fooRequest->state);
    assertType("'draft'|'published'", $fooRequest->status);
    assertType("'draft'|'published'", $fooRequest->stringStatus);
    assertType('(1|2|numeric-string)', $fooRequest->priority);
    assertType("'draft'|'published'", $fooRequest->arrayableState);
    assertType("'Admin'", $fooRequest->objectState);
    assertType('non-empty-string', $fooRequest->escapedState);
    assertType("'draft'|'published'", $fooRequest->untypedState);
    assertType('non-empty-string', $fooRequest->uncertainState);
    assertType("array<'draft'|'published'>", $fooRequest->arrayIn);
    assertType("list<'draft'|'published'>", $fooRequest->listIn);
    assertType("array<'draft'|'published'>", $fooRequest->arrayRuleIn);
    assertType("list<'draft'|'published'>", $fooRequest->listRuleIn);
    assertType('array', $fooRequest->numericArrayIn);
    assertType('array', $fooRequest->unknownArrayIn);
    assertType("array<'LIT'|'NYC'>", $fooRequest->airports);
    assertType('float|int|numeric-string', $fooRequest->integerValue);
    assertType('string|null', $fooRequest->extension);
}

/** @param (Closure(): 'fallback')|'time'|InvokableDefault $default */
function testValidatedDefaults(SafeReturnRequest $request, Closure|string|InvokableDefault $default): void
{
    assertType("'fallback'|'time'|App\\ValueObjects\\InvokableDefault", $request->validated('missing', $default));
}

/** @param list<string>|null $maybeKeys */
function testSafeSelectorsAndNull(SelectorRequest $request, array|null $maybeKeys): void
{
    assertType('array{profile: array{first: non-empty-string}}', $request->safe(['profile.first']));
    assertType('array<string, mixed>|Illuminate\\Support\\ValidatedInput', $request->safe($maybeKeys));
}

function testAllowedKeys(AllowedKeysRequest $request): void
{
    assertType('array{name?: string, other?: mixed}|null', $request->validated('conditionalPruning'));
    assertType('array{name?: string, ...}|null', $request->validated('unknownKeys'));
    assertType('array{name?: string, ...}', $request->validated('nonEmptyKeys'));
}

function testExcludedChildren(AllowedKeysRequest $request): void
{
    assertType('array', $request->validated('items'));
    assertType('array{}|null', $request->validated('unruled'));
}

function testNumericSelectors(SelectorRequest $request): void
{
    assertType('array{}', $request->safe(['0']));
}

function testNumericArrayKeys(SelectorRequest $request): void
{
    assertType('array{numeric?: array{0?: mixed}}', $request->safe(['numeric.0']));
}

function testTraitRules(StringRequest $string): void
{
    assertType('non-empty-string', $string->local);
}

class AllowedKeysRequest extends FormRequest
{
    /** @var list<string> */
    private array $keys = ['name'];

    /** @var non-empty-list<string> */
    private array $nonEmptyKeys = ['name', 'other', 'kept'];

    public function rules(): array
    {
        return [
            'object' => ['required', Rule::array(['name', 'other'])],
            'object.name' => 'string',
            'string' => 'required|array:name,other',
            'string.name' => 'string',
            'nested' => ['required', Rule::array(['name'])],
            'nested.name' => 'array',
            'nested.name.first' => 'required|string',
            'pruned' => ['required', 'array', Rule::array(['name', 'other'])],
            'pruned.name' => 'string',
            'excluded' => ['required', Rule::array(['name', 'other'])],
            'excluded.name' => 'exclude',
            'conditional' => 'required|array:name,other',
            'conditional.name' => 'exclude_if:flag,true|string',
            'conditionalPruning' => ['required', Rule::array(['name', 'other']), Rule::when($this->boolean('flag'), 'array')],
            'conditionalPruning.name' => 'string',
            'unknownKeys' => ['required', Rule::array($this->keys)],
            'unknownKeys.name' => 'string',
            'nonEmptyKeys' => ['required', Rule::array($this->nonEmptyKeys)],
            'nonEmptyKeys.name' => 'string',
            'numeric' => 'required|array:0,1',
            'serialized' => ['required', Rule::array(['first,last'])],
            'payload' => 'required|array',
            'payload.name' => 'exclude',
            'items' => 'required|array',
            'items.*.name' => 'exclude',
            'arrays' => 'required|array',
            'arrays.*' => 'array',
            'arrays.*.name' => 'exclude',
            'unruled.name' => 'exclude',
        ];
    }
}

class SelectorRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'numeric' => 'required|array:0,1',
            'profile.first' => 'required|string',
            'profile.last' => 'required|string',
            'profile.{first}' => 'required|string',
            'profile.{last}' => 'required|string',
        ];
    }
}

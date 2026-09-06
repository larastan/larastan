<?php

declare(strict_types=1);

namespace FormRequestFeatureEnabled;

use App\Http\Requests\RequestPriority;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;

use function PHPStan\Testing\assertType;

/** @implements Arrayable<int, string> */
class AllowedKeys implements Arrayable
{
    /** @return array<int, string> */
    public function toArray(): array
    {
        return ['name'];
    }
}

class EnabledRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }

    protected function prepareForValidation(): void
    {
        $alias = $this;

        if (is_int($alias->name)) {
            $this->merge(['name' => (string) $alias->name]);
        }
    }
}

class NumericEnumRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['priority' => ['required', Rule::enum(RequestPriority::class)]];
    }
}

function acceptsInteger(int $value): void
{
}

function hasNoncanonicalPriority(NumericEnumRequest $request): bool
{
    $priority = $request->validated('priority');
    acceptsInteger($priority);

    return $priority === '01';
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
    assertType('array{name: string}', $request->validated());
    assertType('string', $request->validated('name'));
    assertType('Illuminate\\Support\\ValidatedInput<array{name: string}>', $request->safe());
    assertType('array{name: string}', $request->safe(['name']));

    acceptsString($request->name);
    acceptsString($request->validated('missing', 'time'));
    acceptsIn(Rule::in(['enabled']));
    Rule::array(new AllowedKeys());
}

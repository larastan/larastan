<?php

declare(strict_types=1);

namespace FormRequestAmbiguousRules;

use App\Http\Requests\RequestPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use function PHPStan\Testing\assertType;

final class OptionalRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'value' => $this->valueRules(),
            'payload' => $this->payloadRules(),
            'payload.name' => 'required|string',
            'stable' => 'required|string',
            'conditionalNullable' => ['present', 'string', Rule::when(true, $this->nullableRules())],
        ];
    }

    /** @return array{0: 'required', 1?: 'string'} */
    private function valueRules(): array
    {
        return $this->boolean('restrict') ? ['required', 'string'] : ['required'];
    }

    /** @return array{0: 'array', 1?: 'exclude'} */
    private function payloadRules(): array
    {
        return $this->boolean('exclude') ? ['array', 'exclude'] : ['array'];
    }

    /** @return array{0?: 'nullable'} */
    private function nullableRules(): array
    {
        return $this->boolean('nullable') ? ['nullable'] : [];
    }
}

final class EnumRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'priority' => ['required', Rule::enum(RequestPriority::class)],
            'stringPriority' => ['required', 'string', Rule::enum(RequestPriority::class)],
        ];
    }
}

function test(OptionalRulesRequest $optional, EnumRequest $enum): void
{
    assertType('mixed', $optional->value);
    assertType('mixed', $optional->payload);
    assertType('array{value?: mixed, payload?: mixed, stable: string, conditionalNullable?: mixed}', $optional->validated());
    assertType('mixed', $optional->conditionalNullable);
    assertType('mixed', $optional->validated('payload.name'));
    assertType('(1|2|numeric-string)', $enum->priority);
    assertType('numeric-string', $enum->stringPriority);
    assertType('(1|2|numeric-string)', $enum->validated('priority'));

    if ($enum->validated('priority') === '01') {
        assertType("'01'", $enum->validated('priority'));
    }
}

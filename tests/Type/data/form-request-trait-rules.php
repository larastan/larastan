<?php

declare(strict_types=1);

namespace FormRequestTraitRules\Shared {
    const IMPORTED_RULE = 'required|string';

    /** @return 'required|string' */
    function importedRule(): string
    {
        return 'required|string';
    }
}

namespace FormRequestTraitRules\Declaration {
    use Illuminate\Validation\Rule as ValidationRule;

    use function FormRequestTraitRules\Shared\importedRule;

    use const FormRequestTraitRules\Shared\IMPORTED_RULE;

    const FIELD_RULE = 'required|string';

    trait ProvidesRules
    {
        public function rules(): array
        {
            return [
                'local' => FIELD_RULE,
                'imported' => IMPORTED_RULE,
                'function' => importedRule(),
                'choice' => ['required', ValidationRule::in(['first', 'second'])],
                'self' => 'required|' . self::FIELD_TYPE,
                'static' => 'required|' . static::FIELD_TYPE,
                'namespace' => 'required|in:' . __NAMESPACE__,
            ];
        }
    }
}

namespace FormRequestTraitRules\Consumer {
    use FormRequestTraitRules\Declaration\ProvidesRules;
    use Illuminate\Foundation\Http\FormRequest;

    use function PHPStan\Testing\assertType;

    const FIELD_RULE = 'required|integer';
    const IMPORTED_RULE = 'required|integer';

    /** @return 'required|integer' */
    function importedRule(): string
    {
        return 'required|integer';
    }

    class StringRequest extends FormRequest
    {
        use ProvidesRules;

        final protected const FIELD_TYPE = 'string';
    }

    class IntegerRequest extends FormRequest
    {
        use ProvidesRules;

        final protected const FIELD_TYPE = 'integer';
    }

    class InheritedRequest extends StringRequest
    {
    }

    function test(StringRequest $string, IntegerRequest $integer, InheritedRequest $inherited): void
    {
        assertType('string', $string->local);
        assertType('string', $string->imported);
        assertType('string', $string->function);
        assertType("'first'|'second'", $string->choice);
        assertType('string', $string->self);
        assertType('string', $string->static);
        assertType("'FormRequestTraitRules\\\\Declaration'", $string->namespace);
        assertType('string', $string->validated('local'));
        assertType('string', $inherited->validated('local'));
        assertType('string', $inherited->self);
        assertType('string', $inherited->static);
        assertType('string', $integer->validated('local'));
        assertType('(float|int|numeric-string|true)', $integer->self);
        assertType('(float|int|numeric-string|true)', $integer->static);
    }
}

<?php

declare(strict_types=1);

namespace App\Traits\FormRequestRules\Shared {
    const IMPORTED_RULE = 'required|string';

    /** @return 'required|string' */
    function importedRule(): string
    {
        return 'required|string';
    }
}

namespace App\Traits\FormRequestRules {
    use Illuminate\Validation\Rule as ValidationRule;

    use function App\Traits\FormRequestRules\Shared\importedRule;

    use const App\Traits\FormRequestRules\Shared\IMPORTED_RULE;

    const FIELD_RULE = 'required|string';

    trait ProvidesRules
    {
        public function rules(): array
        {
            return [
                'fromTrait' => 'required|integer',
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

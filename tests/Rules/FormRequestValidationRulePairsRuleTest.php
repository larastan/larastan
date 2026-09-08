<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\FormRequestValidationRulePairsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FormRequestValidationRulePairsRule> */
class FormRequestValidationRulePairsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new FormRequestValidationRulePairsRule($this->createReflectionProvider());
    }

    public function testSimpleDeclarations(): void
    {
        $this->analyse([__DIR__ . '/data/form-request-validation-rule-pairs.php'], [
            ["Field 'email' has a 'nullable' rule but does not accept null.", 12, "The 'required' rule rejects null even when 'nullable' is present. Decide whether null should be valid for this field."],
            ["Field 'absent' has conflicting validation rules 'required' and 'missing'.", 12, "The 'required' and 'missing' rules require the field to be both present and absent. Choose whether the field must be present or absent."],
            ["Field 'triple' has conflicting validation rules 'required' and 'missing'.", 12, "The 'required' and 'missing' rules require the field to be both present and absent. Choose whether the field must be present or absent."],
            ["Field 'regex' has a 'nullable' rule but does not accept null.", 12, "The 'required' rule rejects null even when 'nullable' is present. Decide whether null should be valid for this field."],
            ["Field 'email' has a 'nullable' rule but does not accept null.", 57, "The 'required' rule rejects null even when 'nullable' is present. Decide whether null should be valid for this field."],
            ["Field 'email' has a 'nullable' rule but does not accept null.", 98, "The 'required' rule rejects null even when 'nullable' is present. Decide whether null should be valid for this field."],
        ]);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

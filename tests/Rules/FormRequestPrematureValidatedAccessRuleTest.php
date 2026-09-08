<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\FormRequestPrematureValidatedAccessRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<FormRequestPrematureValidatedAccessRule> */
class FormRequestPrematureValidatedAccessRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new FormRequestPrematureValidatedAccessRule();
    }

    public function testEarlyHooks(): void
    {
        $errors = [];

        foreach (
            [
                [13, 'validated', 'prepareForValidation'],
                [14, 'safe', 'prepareForValidation'],
                [27, 'validated', 'authorize'],
                [33, 'safe', 'rules'],
                [39, 'validated', 'validationData'],
                [45, 'validated', 'messages'],
                [51, 'safe', 'attributes'],
                [58, 'validated', 'withValidator'],
                [60, 'safe', 'withValidator'],
                [67, 'validated', 'after'],
            ] as [$line, $method, $hook]
        ) {
            $errors[] = [
                'Method FormRequestPrematureAccess\\EarlyRequest::' . $method . '() should not be called in ' . $hook . '().',
                $line,
                'Laravel normally initializes the request validator later. Use input() for unvalidated request data, or move work requiring validated data to passedValidation() or the controller.',
            ];
        }

        $this->analyse([__DIR__ . '/data/form-request-premature-validated-access.php'], $errors);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

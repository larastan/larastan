<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoAuthHelperInRequestScopeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoAuthHelperInRequestScopeRule> */
class NoAuthHelperInRequestScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoAuthHelperInRequestScopeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/no-auth-helper-in-request-scope-rule.php'], [
            ['Do not use auth()->check() in a class that has access to the request. Use $request->user() !== null instead.', 23],
            ['Do not use auth()->user() in a class that has access to the request. Use $request->user() instead.', 28],
            ['Do not use auth()->guest() in a class that has access to the request. Use $request->user() === null instead.', 33],
            ['Do not use auth()->check() in a class that has access to the request. Use $this->user() !== null instead.', 51],
        ]);
    }

    public function testFix(): void
    {
        $this->fix(__DIR__ . '/data/no-auth-helper-in-request-scope-rule.php', __DIR__ . '/data/no-auth-helper-in-request-scope-rule-fixed.php');
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/phpstan-rules.neon',
        ];
    }
}

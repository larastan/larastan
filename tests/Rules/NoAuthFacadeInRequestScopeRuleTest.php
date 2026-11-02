<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoAuthFacadeInRequestScopeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoAuthFacadeInRequestScopeRule> */
class NoAuthFacadeInRequestScopeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoAuthFacadeInRequestScopeRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/no-auth-facade-in-request-scope-rule.php'], [
            ['Do not use Auth::check() in a class that has access to the request. Use $request->user() !== null instead.', 17],
            ['Do not use Auth::user() in a class that has access to the request. Use $request->user() instead.', 22],
            ['Do not use Auth::guest() in a class that has access to the request. Use $request->user() === null instead.', 27],
            ['Do not use Auth::check() in a class that has access to the request. Use $this->user() !== null instead.', 40],
        ]);
    }

    public function testFix(): void
    {
        $this->fix(__DIR__ . '/data/no-auth-facade-in-request-scope-rule.php', __DIR__ . '/data/no-auth-facade-in-request-scope-rule-fixed.php');
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/phpstan-rules.neon',
        ];
    }
}

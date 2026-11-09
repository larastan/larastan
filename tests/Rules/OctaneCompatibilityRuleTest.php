<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\OctaneCompatibilityRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<OctaneCompatibilityRule> */
class OctaneCompatibilityRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new OctaneCompatibilityRule();
    }

    public function testNoContainerInjection(): void
    {
        $this->analyse([__DIR__ . '/data/ContainerInjection.php'], [
            ['Consider using bind method instead or pass a closure.', 13, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 17, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 26, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 30, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 34, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 47, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 52, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            // The same bindings written as arrow functions.
            ['Consider using bind method instead or pass a closure.', 61, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 65, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 70, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 72, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            // Non-first arguments, make(), get(), makeWith(), and class-string references
            ['Consider using bind method instead or pass a closure.', 105, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 110, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 115, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 120, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 125, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 130, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 135, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            // The cases from issue #2597 as reported
            ['Consider using bind method instead or pass a closure.', 151, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 152, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 153, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 154, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 155, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 156, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

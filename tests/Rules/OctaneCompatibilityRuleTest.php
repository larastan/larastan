<?php

declare(strict_types=1);

namespace Tests\Rules;

use NunoMaduro\Larastan\Rules\OctaneCompatibilityRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OctaneCompatibilityRule>
 */
class OctaneCompatibilityRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new OctaneCompatibilityRule();
    }

    public function testNoContainerInjection(): void
    {
        $this->analyse([__DIR__.'/Data/ContainerInjection.php'], [
            ['Consider using bind method instead or pass a closure.', 12, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 16, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 25, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 29, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 33, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 46, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
            ['Consider using bind method instead or pass a closure.', 51, 'See: https://laravel.com/docs/octane#dependency-injection-and-octane'],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/../phpstan-tests.neon'];
    }
}

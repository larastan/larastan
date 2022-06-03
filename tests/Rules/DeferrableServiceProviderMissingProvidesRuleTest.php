<?php
declare(strict_types=1);

namespace Rules;

use NunoMaduro\Larastan\Rules\DeferrableServiceProviderMissingProvidesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

class DeferrableServiceProviderMissingProvidesRuleTest extends RuleTestCase
{
    public function testNoFalsePositivesDirectExistingMethod(): void
    {
        $this->analyse(
            [
                __DIR__.'/Data/CorrectDeferrableProvider.php',
            ],
            []
        );
    }

    public function testNoFalsePositivesIndirectExistingMethod(): void
    {
        $this->analyse(
            [
                __DIR__.'/Data/CorrectDeferrableProviderIndirect.php',
            ],
            []
        );
    }

    public function testIncorrectDeferrableServiceProvider(): void
    {
        $this->analyse(
            [
                __DIR__.'/Data/IncorrectDeferrableProvider.php',
            ],
            [
                ['ServiceProviders that implement the "DeferrableProvider" interface should implement the "provides" method that returns an array of strings or class-strings', 9],
            ]
        );
    }

    protected function getRule(): Rule
    {
        return new DeferrableServiceProviderMissingProvidesRule();
    }
}

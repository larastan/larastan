<?php

declare(strict_types=1);

namespace Tests\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Larastan\Larastan\Rules\ArrGetPullRule;

/** @extends RuleTestCase<ArrGetPullRule> */
class ArrGetPullRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ArrGetPullRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/data/arr-get-pull.php'], [
            ["Key 'bar' does not exist in array{foo: 1}.", 13],
            ["Key 'bar' does not exist in array{foo: 1}.", 16],
            ["Key 'bar' does not exist in array<'foo', mixed>.", 18],
            ["Key 'bar' does not exist in array<'foo', mixed>.", 19],
            ["Key 3 does not exist in array{1, 2, 3}.", 27],
            ["Key 3 does not exist in array{1, 2, 3}.", 29],
            ["Key 'invalid' does not exist in array{foo: 1, bar: 2}.", 41],
            ["Key 'invalid' does not exist in array{foo: 1, bar: 2}.", 42],
        ]);
    }

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/phpstan-rules.neon',
		];
	}
}

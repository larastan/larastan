<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\AggregateRelationCallbackKeyRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<AggregateRelationCallbackKeyRule> */
final class AggregateRelationCallbackKeyRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(AggregateRelationCallbackKeyRule::class);
    }

    public function testChecksIndividualAggregateKeys(): void
    {
        $message = 'Aggregate callback for relation "accounts" must accept Illuminate\\Database\\Eloquent\\Builder<App\\Account>, but its first parameter requires App\\PostBuilder.';

        $this->analyse([__DIR__ . '/data/aggregate-relation-callback-keys.php'], [
            [$message, 33],
            [$message, 38],
            [$message, 42],
            [$message, 48],
        ]);
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-rules.neon'];
    }
}

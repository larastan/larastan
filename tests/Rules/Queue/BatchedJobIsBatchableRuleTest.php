<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\BatchedJobIsBatchableRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<BatchedJobIsBatchableRule> */
class BatchedJobIsBatchableRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(BatchedJobIsBatchableRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/bus-batch.php'], [
            [
                "Job Tests\\Rules\\Queue\\Data\\RegularJob is batched but does not use Batchable.\n    💡 Use the Illuminate\\Bus\\Batchable trait.",
                11,
            ],
            [
                "Job Tests\\Rules\\Queue\\Data\\RegularJob is batched but does not use Batchable.\n    💡 Use the Illuminate\\Bus\\Batchable trait.",
                18,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

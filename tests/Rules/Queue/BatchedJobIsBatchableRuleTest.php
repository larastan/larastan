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
                "Job 'Tests\Rules\Queue\Data\RegularJob' is dispatched in 'Bus::batch()' but does not use the Batchable trait, so it has no '\$this->batch()' accessor and the batch cannot track it. Add 'use Illuminate\Bus\Batchable;' to the job.",
                11,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\RegularJob' is dispatched in 'Bus::batch()' but does not use the Batchable trait, so it has no '\$this->batch()' accessor and the batch cannot track it. Add 'use Illuminate\Bus\Batchable;' to the job.",
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

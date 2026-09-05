<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\BatchableJobChecksCancellationRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<BatchableJobChecksCancellationRule> */
class BatchableJobChecksCancellationRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(BatchableJobChecksCancellationRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/batchable-jobs.php'], [
            [
                "Job 'Tests\Rules\Queue\Data\BatchableJobWithoutCancellationCheck' uses the Batchable trait but never checks whether its batch has been cancelled, so it still runs its full body for an abandoned batch. Guard the work with 'if (\$this->batch()?->cancelled()) { return; }' at the start of handle(), or register the 'SkipIfBatchCancelled' middleware.",
                74,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\ConcreteBatchableJobFromAbstractBase' uses the Batchable trait but never checks whether its batch has been cancelled, so it still runs its full body for an abandoned batch. Guard the work with 'if (\$this->batch()?->cancelled()) { return; }' at the start of handle(), or register the 'SkipIfBatchCancelled' middleware.",
                84,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

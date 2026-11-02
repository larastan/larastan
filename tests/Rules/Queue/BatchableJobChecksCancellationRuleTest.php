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
                "Batchable job Tests\\Rules\\Queue\\Data\\BatchableJobWithoutCancellationCheck does not check for batch cancellation.\n    💡 Check \$this->batch()?->cancelled() or use the SkipIfBatchCancelled middleware.",
                74,
            ],
            [
                "Batchable job Tests\\Rules\\Queue\\Data\\ConcreteBatchableJobFromAbstractBase does not check for batch cancellation.\n    💡 Check \$this->batch()?->cancelled() or use the SkipIfBatchCancelled middleware.",
                84,
            ],
            [
                "Batchable job Tests\\Rules\\Queue\\Data\\JobOverridingCancellationGuard does not check for batch cancellation.\n    💡 Check \$this->batch()?->cancelled() or use the SkipIfBatchCancelled middleware.",
                125,
            ],
            [
                "Batchable job Tests\\Rules\\Queue\\Data\\JobOverridingCancellationMiddleware does not check for batch cancellation.\n    💡 Check \$this->batch()?->cancelled() or use the SkipIfBatchCancelled middleware.",
                132,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

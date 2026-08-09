<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\JobDispatchedInTransactionUsesAfterCommitRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<JobDispatchedInTransactionUsesAfterCommitRule> */
class JobDispatchedInTransactionUsesAfterCommitRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(JobDispatchedInTransactionUsesAfterCommitRule::class);
    }

    public function testRule(): void
    {
        $message = "Job 'Tests\Rules\Queue\Data\NotifyOwner' is dispatched inside 'DB::transaction()' without '->afterCommit()', so a worker can pick it up before the transaction commits, or run it against rows a rollback threw away. Chain '->afterCommit()' on the dispatch, or declare 'public bool \$afterCommit = true;' on the job.";

        $this->analyse([__DIR__ . '/data/transaction-dispatch.php'], [
            [$message, 40],
            [$message, 44],
            [$message, 48],
            [$message, 51],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

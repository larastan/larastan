<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\JobDispatchedInTransactionUsesAfterCommitRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function Orchestra\Testbench\laravel_version_compare;
use function str_replace;

/** @extends RuleTestCase<JobDispatchedInTransactionUsesAfterCommitRule> */
class JobDispatchedInTransactionUsesAfterCommitRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(JobDispatchedInTransactionUsesAfterCommitRule::class);
    }

    public function testRule(): void
    {
        $message = "Job Tests\\Rules\\Queue\\Data\\NotifyOwner is dispatched inside a transaction without deferring until commit.\n    💡 Call afterCommit() on the dispatch or implement ShouldQueueAfterCommit.";

        $this->analyse([__DIR__ . '/data/transaction-dispatch.php'], [
            [$message, 40],
            [$message, 44],
            [$message, 48],
            [$message, 51],
            [str_replace('NotifyOwner', 'QueueableNotifyOwner', $message), 107],
            [str_replace('NotifyOwner', 'QueueableNotifyOwnerAfterCommit', $message), 109],
        ]);
    }

    /** @param list<array{string, int}> $errors */
    #[DataProvider('contractErrors')]
    public function testAfterCommitContract(array $errors): void
    {
        $this->analyse([__DIR__ . '/data/transaction-aftercommit-contract.php'], $errors);
    }

    /** @return iterable<array{list<array{string, int}>}> */
    public static function contractErrors(): iterable
    {
        $message = "Job Tests\\Rules\\Queue\\Data\\AfterCommitContractJob is dispatched inside a transaction without deferring until commit.\n    💡 Call afterCommit() on the dispatch or implement ShouldQueueAfterCommit.";

        if (laravel_version_compare('12.22.0', '<')) {
            yield [[]];

            return;
        }

        yield [
            [
                [$message, 25],
                [str_replace('AfterCommitContractJob', 'BeforeCommitContractJob', $message), 27],
            ],
        ];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

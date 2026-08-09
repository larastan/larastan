<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\NoBatchedUniqueJobRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoBatchedUniqueJobRule> */
class NoBatchedUniqueJobRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoBatchedUniqueJobRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/batched-unique-jobs.php'], [
            [
                "Job 'Tests\Rules\Queue\Data\UniqueJobWithUniqueForProperty' implements ShouldBeUnique and must not be dispatched via 'batch()'. Bulk and batch dispatch bypass the uniqueness lock, dispatch the job individually instead.",
                11,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\UniqueJobWithUniqueForMethod' implements ShouldBeUnique and must not be dispatched via 'bulk()'. Bulk and batch dispatch bypass the uniqueness lock, dispatch the job individually instead.",
                16,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\UniqueJobWithUniqueForProperty' implements ShouldBeUnique and must not be dispatched via 'bulk()'. Bulk and batch dispatch bypass the uniqueness lock, dispatch the job individually instead.",
                20,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\UniqueJobWithUniqueForProperty' implements ShouldBeUnique and must not be dispatched via 'batch()'. Bulk and batch dispatch bypass the uniqueness lock, dispatch the job individually instead.",
                27,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\UniqueJobWithUniqueForProperty' implements ShouldBeUnique and must not be dispatched via 'batch()'. Bulk and batch dispatch bypass the uniqueness lock, dispatch the job individually instead.",
                43,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

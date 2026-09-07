<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\UniqueJobDeclaresUniqueIdRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UniqueJobDeclaresUniqueIdRule> */
class UniqueJobDeclaresUniqueIdRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(UniqueJobDeclaresUniqueIdRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/unique-jobs.php'], [
            [
                "Job 'Tests\Rules\Queue\Data\ParameterizedUniqueJobWithoutUniqueId' implements ShouldBeUnique and is parameterized but does not declare uniqueId, so every dispatch shares one lock key whatever the constructor arguments and distinct jobs are silently dropped. Add a 'uniqueId()' method derived from the distinguishing arguments, or return a constant from it for an intentionally class wide job.",
                65,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\UniqueUntilProcessingJob' implements ShouldBeUnique and is parameterized but does not declare uniqueId, so every dispatch shares one lock key whatever the constructor arguments and distinct jobs are silently dropped. Add a 'uniqueId()' method derived from the distinguishing arguments, or return a constant from it for an intentionally class wide job.",
                108,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

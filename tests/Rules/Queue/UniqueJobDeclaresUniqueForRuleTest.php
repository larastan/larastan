<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\UniqueJobDeclaresUniqueForRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UniqueJobDeclaresUniqueForRule> */
class UniqueJobDeclaresUniqueForRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(UniqueJobDeclaresUniqueForRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/unique-jobs.php'], [
            [
                "Job Tests\\Rules\\Queue\\Data\\UniqueJobWithoutUniqueFor implements ShouldBeUnique but does not declare uniqueFor.\n    💡 Declare a \$uniqueFor property or a uniqueFor() method.",
                47,
            ],
            [
                "Job Tests\\Rules\\Queue\\Data\\UniqueUntilProcessingJob implements ShouldBeUnique but does not declare uniqueFor.\n    💡 Declare a \$uniqueFor property or a uniqueFor() method.",
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

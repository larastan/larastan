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
                "Unique job Tests\\Rules\\Queue\\Data\\ParameterizedUniqueJobWithoutUniqueId has constructor parameters but does not declare uniqueId.\n    💡 Declare a uniqueId() method or a \$uniqueId property to identify distinct jobs.",
                65,
            ],
            [
                "Unique job Tests\\Rules\\Queue\\Data\\UniqueUntilProcessingJob has constructor parameters but does not declare uniqueId.\n    💡 Declare a uniqueId() method or a \$uniqueId property to identify distinct jobs.",
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

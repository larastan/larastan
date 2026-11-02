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
                "Unique job Tests\\Rules\\Queue\\Data\\UniqueJobWithUniqueForProperty is dispatched via batch().\n    💡 Dispatch unique jobs individually to preserve uniqueness.",
                11,
            ],
            [
                "Unique job Tests\\Rules\\Queue\\Data\\UniqueJobWithUniqueForMethod is dispatched via bulk().\n    💡 Dispatch unique jobs individually to preserve uniqueness.",
                16,
            ],
            [
                "Unique job Tests\\Rules\\Queue\\Data\\UniqueJobWithUniqueForProperty is dispatched via bulk().\n    💡 Dispatch unique jobs individually to preserve uniqueness.",
                20,
            ],
            [
                "Unique job Tests\\Rules\\Queue\\Data\\UniqueJobWithUniqueForProperty is dispatched via batch().\n    💡 Dispatch unique jobs individually to preserve uniqueness.",
                27,
            ],
            [
                "Unique job Tests\\Rules\\Queue\\Data\\UniqueJobWithUniqueForProperty is dispatched via batch().\n    💡 Dispatch unique jobs individually to preserve uniqueness.",
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

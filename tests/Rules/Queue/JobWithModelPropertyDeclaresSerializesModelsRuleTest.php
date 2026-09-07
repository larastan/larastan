<?php

declare(strict_types=1);

namespace Tests\Rules\Queue;

use Larastan\Larastan\Rules\Queue\JobWithModelPropertyDeclaresSerializesModelsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<JobWithModelPropertyDeclaresSerializesModelsRule> */
class JobWithModelPropertyDeclaresSerializesModelsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(JobWithModelPropertyDeclaresSerializesModelsRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/serializes-models.php'], [
            [
                "Job 'Tests\Rules\Queue\Data\JobWithModelPropertyWithoutSerializesModels' holds Eloquent model in public property (\$product) but does not use the SerializesModels trait, so each model is serialized whole onto the queue and rehydrated from a stale dispatch time snapshot. Add 'use Illuminate\Queue\SerializesModels;' to the job.",
                68,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\JobWithNullableModelPropertyWithoutSerializesModels' holds Eloquent model in public property (\$product) but does not use the SerializesModels trait, so each model is serialized whole onto the queue and rehydrated from a stale dispatch time snapshot. Add 'use Illuminate\Queue\SerializesModels;' to the job.",
                75,
            ],
            [
                "Job 'Tests\Rules\Queue\Data\JobWithMultipleModelPropertiesWithoutSerializesModels' holds Eloquent models in public properties (\$product, \$invoice) but does not use the SerializesModels trait, so each model is serialized whole onto the queue and rehydrated from a stale dispatch time snapshot. Add 'use Illuminate\Queue\SerializesModels;' to the job.",
                80,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

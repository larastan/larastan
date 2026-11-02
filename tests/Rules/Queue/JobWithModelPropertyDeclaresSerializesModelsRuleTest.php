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
                "Job Tests\\Rules\\Queue\\Data\\JobWithModelPropertyWithoutSerializesModels has model properties (\$product) but does not use SerializesModels.\n    💡 Use the Illuminate\\Queue\\SerializesModels trait.",
                68,
            ],
            [
                "Job Tests\\Rules\\Queue\\Data\\JobWithNullableModelPropertyWithoutSerializesModels has model properties (\$product) but does not use SerializesModels.\n    💡 Use the Illuminate\\Queue\\SerializesModels trait.",
                75,
            ],
            [
                "Job Tests\\Rules\\Queue\\Data\\JobWithMultipleModelPropertiesWithoutSerializesModels has model properties (\$product, \$invoice) but does not use SerializesModels.\n    💡 Use the Illuminate\\Queue\\SerializesModels trait.",
                80,
            ],
            [
                "Job Tests\\Rules\\Queue\\Data\\JobInheritingModelProperty has model properties (\$product) but does not use SerializesModels.\n    💡 Use the Illuminate\\Queue\\SerializesModels trait.",
                87,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../../phpstan-tests.neon'];
    }
}

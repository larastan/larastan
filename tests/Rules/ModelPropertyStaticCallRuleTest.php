<?php

declare(strict_types=1);

namespace Tests\Rules;

use NunoMaduro\Larastan\Rules\ModelProperties\ModelPropertiesRuleHelper;
use NunoMaduro\Larastan\Rules\ModelProperties\ModelPropertyStaticCallRule;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ModelPropertyStaticCallRule> */
class ModelPropertyStaticCallRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ModelPropertyStaticCallRule(
            $this->createReflectionProvider(),
            new ModelPropertiesRuleHelper,
            $this->getContainer()->getByType(RuleLevelHelper::class),
        );
    }

    public function testModelPropertyRuleOnStaticCallsToModel(): void
    {
        $this->analyse([__DIR__.'/Data/model-property-static-call.php'], [
            ['Property \'foo\' does not exist in App\\User model.', 7],
            ['Property \'foo\' does not exist in App\\User model.', 13],
            ['Property \'foo\' does not exist in App\\User model.', 18],
        ]);
    }

    public function testModelPropertyRuleOnStaticCallsInClass(): void
    {
        $this->analyse([__DIR__.'/Data/ModelPropertyStaticCallsInClass.php'], [
            ['Property \'foo\' does not exist in Tests\\Rules\\Data\\ModelPropertyStaticCallsInClass model.', 16],
            ['Property \'foo\' does not exist in Tests\\Rules\\Data\\ModelPropertyStaticCallsInClass model.', 24],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/Data/modelPropertyConfig.neon',
        ];
    }
}

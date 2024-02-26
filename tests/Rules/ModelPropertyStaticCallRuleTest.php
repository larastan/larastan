<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ModelProperties\ModelPropertiesRuleHelper;
use Larastan\Larastan\Rules\ModelProperties\ModelPropertyStaticCallRule;
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
            new ModelPropertiesRuleHelper(),
            $this->getContainer()->getByType(RuleLevelHelper::class),
        );
    }

    public function testModelPropertyRuleOnStaticCallsToModel(): void
    {
        $this->analyse([__DIR__ . '/data/model-property-static-call.php'], [
            ['Property \'foo\' does not exist in App\\User model.', 7],
            ['Property \'foo\' does not exist in App\\User model.', 13],
            ['Property \'foo\' does not exist in App\\User model.', 18],
        ]);
    }

    public function testModelPropertyRuleOnStaticCallsInClass(): void
    {
        $this->analyse([__DIR__ . '/data/ModelPropertyStaticCallsInClass.php'], [
            ['Property \'foo\' does not exist in Tests\\Rules\\Data\\ModelPropertyStaticCallsInClass model.', 16],
            ['Property \'foo\' does not exist in Tests\\Rules\\Data\\ModelPropertyStaticCallsInClass model.', 24],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/data/modelPropertyConfig.neon',
        ];
    }
}

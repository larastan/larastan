<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class ModelPropertyStaticCallRuleTest extends RulesTest
{
    public function testModelPropertyRuleOnStaticCallsToModel(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-static-call.php');

        self::assertEquals([
            7 => 'Property \'foo\' does not exist in App\\User model.',
            13 => 'Property \'foo\' does not exist in App\\User model.',
            18 => 'Property \'foo\' does not exist in App\\User model.',
        ], $errors);
    }

    public function testModelPropertyRuleOnStaticCallsInClass(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/ModelPropertyStaticCallsInClass.php');

        self::assertEquals([
            16 => 'Property \'foo\' does not exist in Tests\\Rules\\Data\\ModelPropertyStaticCallsInClass model.',
            24 => 'Property \'foo\' does not exist in Tests\\Rules\\Data\\ModelPropertyStaticCallsInClass model.',
        ], $errors);
    }
}

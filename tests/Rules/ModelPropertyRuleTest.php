<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class ModelPropertyRuleTest extends RulesTest
{
    public function testModelPropertyRuleOnBuilder(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-builder.php');

        self::assertEquals([
            3 => 'Property \'foo\' does not exist in App\\User model.',
            4 => 'Property \'foo\' does not exist in App\\User model.',
            7 => 'Property \'foo\' does not exist in App\\User model.',
            16 => 'Property \'foo\' does not exist in App\\User model.',
            21 => 'Property \'foo\' does not exist in App\\User model.',
        ], $errors);
    }

    public function testModelPropertyRuleOnRelation(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-relation.php');

        self::assertEquals([
            6 => 'Property \'foo\' does not exist in App\\Account model.',
            7 => 'Property \'foo\' does not exist in App\\Account model.',
            8 => 'Property \'foo\' does not exist in App\\Account model.',
            9 => 'Property \'foo\' does not exist in App\\Account model.',
            10 => 'Property \'foo\' does not exist in App\\Account model.',
        ], $errors);
    }

    public function testModelPropertyRuleOnModel(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-model.php');

        self::assertEquals([
            7 => 'Property \'foo\' does not exist in ModelPropertyOnModel model.',
        ], $errors);
    }
}

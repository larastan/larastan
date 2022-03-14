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
            9 => 'Property \'foo\' does not exist in App\\User model.',
            18 => 'Property \'foo\' does not exist in App\\User model.',
            23 => 'Property \'foo\' does not exist in App\\User model.',
            25 => 'Property \'foo\' does not exist in App\\User model.',
            26 => 'Property \'foo\' does not exist in App\\User model.',
            27 => 'Property \'foo\' does not exist in App\\User model.',
            30 => 'Property \'foo\' does not exist in App\\User model.',
        ], $errors);
    }

    public function testModelPropertyRuleOnRelation(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-relation.php');

        self::assertEquals([
            4 => 'Property \'foo\' does not exist in App\\Account model.',
            5 => 'Property \'foo\' does not exist in App\\Account model.',
            6 => 'Property \'foo\' does not exist in App\\Account model.',
            7 => 'Property \'foo\' does not exist in App\\Account model.',
            8 => 'Property \'foo\' does not exist in App\\Account model.',
            10 => 'Property \'foo\' does not exist in App\\Post model. If \'foo\' exists as a column on the pivot table, consider using \'wherePivot\' or prefix the column with table name instead.',
        ], $errors);
    }

    public function testModelPropertyRuleOnModel(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-model.php');

        self::assertEquals([
            9 => 'Property \'foo\' does not exist in ModelPropertyModel\ModelPropertyOnModel model.',
            16 => 'Property \'foo\' does not exist in App\Account|App\User model.',
            23 => 'Property \'name\' does not exist in App\Account|App\User model.',
        ], $errors);
    }

    public function testModelPropertyRuleOnModelFactory(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-model-factory.php');

        self::assertEquals([
            5 => 'Property \'foo\' does not exist in App\\User model.',
        ], $errors);
    }
}

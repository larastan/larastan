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
            8 => 'Property \'foo\' does not exist in App\\User model.',
            17 => 'Property \'foo\' does not exist in App\\User model.',
            22 => 'Property \'foo\' does not exist in App\\User model.',
            24 => 'Property \'foo\' does not exist in App\\User model.',
            25 => 'Property \'foo\' does not exist in App\\User model.',
            26 => 'Property \'foo\' does not exist in App\\User model.',
            29 => 'Property \'foo\' does not exist in App\\User model.',
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
            12 => 'Property \'foo\' does not exist in App\Post model. If \'foo\' exists as a column on the pivot table, consider using \'wherePivot\' or prefix the column with table name instead.',
        ], $errors);
    }

    public function testModelPropertyRuleOnModel(): void
    {
        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-model.php');

        self::assertEquals([
            7 => 'Property \'foo\' does not exist in ModelPropertyOnModel model.',
        ], $errors);
    }

    public function testModelPropertyRuleOnModelFactory(): void
    {
        if (version_compare(app()->version(), '8.0.0', '<')) {
            $this->markTestSkipped('Test required Laravel 8');
        }

        $errors = $this->setConfigPath(__DIR__.DIRECTORY_SEPARATOR.'Data/modelPropertyConfig.neon')->findErrorsByLine(__DIR__.'/Data/model-property-model-factory.php');

        self::assertEquals([
            5 => 'Property \'foo\' does not exist in Laravel8\\Models\\User model.',
        ], $errors);
    }
}

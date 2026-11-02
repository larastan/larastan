<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ModelRelationDefaultsRule;
use Larastan\Larastan\Rules\ModelRuleHelper;
use Larastan\Larastan\Rules\RelationExistenceHelper;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ModelRelationDefaultsRule> */
class ModelRelationDefaultsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ModelRelationDefaultsRule(new RelationExistenceHelper(new ModelRuleHelper()), self::getContainer()->getByType(InitializerExprTypeResolver::class));
    }

    public function testDefaults(): void
    {
        $this->analyse([__DIR__ . '/data/model-relation-defaults.php'], [
            ["Relation 'missing' is not found in ModelRelationDefaults\\InvalidDefaults model.", 19],
            ["Relation 'missing' is not found in App\\Account model.", 19],
            ["Relation 'missing' is not found in ModelRelationDefaults\\InvalidDefaults model.", 20],
            ["Relation 'accounts  as  total' is not found in ModelRelationDefaults\\InvalidDefaults model.", 20],
            ["Relation 'accounts.transactions' is not found in ModelRelationDefaults\\InvalidDefaults model.", 20],
            ["Relation 'children' is not found in ModelRelationDefaults\\InvalidChild model.", 38],
            ["Relation 'children' is not found in ModelRelationDefaults\\InvalidChild model.", 38],
            ["Relation 'missing' is not found in App\\Account model.", 53],
            ["Relation 'missing' is not found in ModelRelationDefaults\\ConstantDefaults model.", 61],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-rules.neon'];
    }
}

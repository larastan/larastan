<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ModelAppendsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ModelAppendsRule> */
class ModelAppendsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ModelAppendsRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/ModelAppends.php'], [
            ["Property 'non_existent' does not exist in model.", 15],
            ["Property 'email' is not a computed property, remove from \$appends.", 16],
            ["Property 'name' is not a computed property, remove from \$appends.", 17],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

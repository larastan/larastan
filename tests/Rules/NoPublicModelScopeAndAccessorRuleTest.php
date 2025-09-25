<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoPublicModelScopeAndAccessorRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoPublicModelScopeAndAccessorRule> */
class NoPublicModelScopeAndAccessorRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoPublicModelScopeAndAccessorRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/no-public-model-scope-and-accessor.php'], [
            ["Local query scope method 'scopePublic' should be declared as protected.", 19],
            ["Model accessor method 'lastName' should be declared as protected.", 32],
            ["Local query scope method 'publicWithAttribute' should be declared as protected.", 46],
            ["Local query scope method 'scopePrivate' should be declared as protected.", 62],
            ["Model accessor method 'privateAccessor' should be declared as protected.", 67],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

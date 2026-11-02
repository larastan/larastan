<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ConfigCollectionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<ConfigCollectionRule> */
class ConfigCollectionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ConfigCollectionRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/config-collection-rule.php'], [
            [
                "Config key 'test.foo' is not an array.",
                11,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../Type/data/config-with-config-paths.neon',
        ];
    }
}

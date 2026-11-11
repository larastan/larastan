<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ConfigCollectionRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use function Orchestra\Testbench\laravel_version_compare;

/** @extends RuleTestCase<ConfigCollectionRule> */
class ConfigCollectionRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(ConfigCollectionRule::class);
    }

    public function testRule(): void
    {
        $errors = [
            [
                "Config key 'test.foo' is not an array.",
                11,
            ],
        ];

        // Named arguments need the method's signature, which Laravel has had since 12.20.
        if (laravel_version_compare('12.20.0', '>=')) {
            $errors[] = [
                "Config key 'test.foo' is not an array.",
                19,
            ];
        }

        $this->analyse([__DIR__ . '/data/config-collection-rule.php'], $errors);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../Type/data/config-with-config-paths.neon',
        ];
    }
}

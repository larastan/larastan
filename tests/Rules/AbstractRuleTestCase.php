<?php

declare(strict_types=1);

namespace Tests\Rules;

use PHPStan\Testing\RuleTestCase;

abstract class AbstractRuleTestCase extends RuleTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/phpstan-rules.neon',
        ];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoModelMakeRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoModelMakeRule> */
class NoModelMakeRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoModelMakeRule($this->createReflectionProvider());
    }

    public function testNoFalsePositives(): void
    {
        $this->analyse([__DIR__ . '/data/CorrectModelInstantiation.php'], []);
    }

    public function testModelMake(): void
    {
        $this->analyse([__DIR__ . '/data/ModelMake.php'], [
            ["Called 'Model::make()' which performs unnecessary work, use 'new Model()'.", 13],
            ["Called 'Model::make()' which performs unnecessary work, use 'new Model()'.", 20],
        ]);
    }
}

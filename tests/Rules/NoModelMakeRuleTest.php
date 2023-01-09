<?php

declare(strict_types=1);

namespace Tests\Rules;

use NunoMaduro\Larastan\Rules\NoModelMakeRule;
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
        $this->analyse([__DIR__.'/Data/CorrectModelInstantiation.php'], []);
    }

    public function testModelMake(): void
    {
        $this->analyse([__DIR__.'/Data/ModelMake.php'], [
            ["Called 'Model::make()' which performs unnecessary work, use 'new Model()'.", 13],
            ["Called 'Model::make()' which performs unnecessary work, use 'new Model()'.", 20],
        ]);
    }
}

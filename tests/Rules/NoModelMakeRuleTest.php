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
        $this->analyse([
            __DIR__ . '/data/CorrectModelInstantiation.php',
            __DIR__ . '/data/ModelMakeTrait.php',
        ], []);
    }

    public function testModelMake(): void
    {
        $this->analyse([__DIR__ . '/data/ModelMake.php'], [
            ["Called 'Model::make()' which performs unnecessary work, use 'new Model()'.", 15],
            ["Called 'Model::make()' which performs unnecessary work, use 'new Model()'.", 22],
        ]);
    }

    public function testReportsModelMakeCallsInTraitRatherThanClass(): void
    {
        $actualErrors = $this->gatherAnalyserErrors([
            __DIR__ . '/data/ModelMake.php',
            __DIR__ . '/data/ModelMakeTrait.php',
        ]);

        $this->assertCount(3, $actualErrors);
        $this->assertSame(
            "Called 'Model::make()' which performs unnecessary work, use 'new Model()'.",
            $actualErrors[0]->getMessage(),
        );
        $this->assertSame(
            __DIR__ . '/data/ModelMakeTrait.php (in context of class Tests\Rules\Data\ModelMake)',
            $actualErrors[0]->getFile(),
        );
        $this->assertSame(13, $actualErrors[0]->getLine());
    }
}

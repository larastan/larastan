<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ArrKeyExistenceRule;
use PHPStan\Rules\Arrays\NonexistentOffsetInArrayDimFetchRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\CompositeRule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<CompositeRule> */
class ArrKeyExistenceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new CompositeRule([
            new ArrKeyExistenceRule(),
            self::getContainer()->getByType(NonexistentOffsetInArrayDimFetchRule::class),
        ]);
    }

    public function testRule(): void
    {
        $shape = 'array{foo: string, bar?: int, nested: array{deep: bool}, word: string}';

        $this->analyse([__DIR__ . '/data/arr-key-existence.php'], [
            ["Offset 'bar' does not exist on array{foo: '123'}.", 15],
            ["Offset 'missing' does not exist on " . $shape . '.', 43],
            ["Offset 'missing' does not exist on " . $shape . '.', 44],
            ["Offset 'missing' does not exist on array{deep: bool}.", 45],
            ["Offset '0' does not exist on string.", 46],
            ["Offset 'missing' does not exist on " . $shape . '.', 47],
            ["Offset 'other' does not exist on " . $shape . '.', 47],
            ["Offset 'missing' does not exist on array{foo: string}|null.", 48],
            ["Offset 'missing' does not exist on " . $shape . '.', 49],
            ["Offset 'missing' does not exist on " . $shape . '.', 50],
            ["Offset 'missing' does not exist on " . $shape . '.', 51],
            ["Offset 'missing' does not exist on " . $shape . '.', 52],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-rules.neon'];
    }
}

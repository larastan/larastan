<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\ModelRuleHelper;
use Larastan\Larastan\Rules\RelationExistenceHelper;
use Larastan\Larastan\Rules\RelationExistenceRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<RelationExistenceRule> */
class RelationExistenceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new RelationExistenceRule(new RelationExistenceHelper(new ModelRuleHelper()));
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/relation-existence-rule.php'], [
            [
                'Relation \'foo\' is not found in App\User model.',
                5,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                6,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                7,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                8,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                9,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                10,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                11,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                12,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                13,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                15,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                16,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                17,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                18,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                19,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                20,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                21,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                22,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                23,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                25,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                26,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                27,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                28,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                29,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                30,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                31,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                32,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                33,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                35,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                36,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                37,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                38,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                39,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                40,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                41,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                42,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                43,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                45,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                46,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                47,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                48,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                49,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                49,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                50,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                53,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                54,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                55,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                56,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                59,
            ],
            [
                'Relation \'bar\' is not found in App\User model.',
                59,
            ],
            [
                'Relation \'foo\' is not found in App\User model.',
                61,
            ],
            [
                'Relation \'foo\' is not found in App\Group model.',
                62,
            ],
            [
                'Relation \'foo\' is not found in App\Account model.',
                63,
            ],
            [
                'Relation \'foo\' is not found in App\Transaction model.',
                64,
            ],
        ]);
    }

    public function testLoading(): void
    {
        $this->analyse([__DIR__ . '/data/relation-existence-loading.php'], [
            ["Relation 'missing' is not found in App\\User model.", 14],
            ["Relation 'missing' is not found in App\\Account model.", 15],
            ["Relation 'missing' is not found in App\\User model.", 16],
            ["Relation 'missing' is not found in App\\Account model.", 17],
            ["Relation 'missing' is not found in App\\User model.", 18],
            ["Relation 'missing' is not found in App\\User model.", 19],
            ["Relation 'missing' is not found in App\\User model.", 20],
            ["Relation 'missing' is not found in App\\User model.", 21],
            ["Relation 'missing' is not found in App\\User model.", 22],
            ["Relation 'missing' is not found in App\\User model.", 23],
            ["Relation 'missing' is not found in App\\User model.", 24],
            ["Relation 'missing' is not found in App\\User model.", 25],
            ["Relation 'missing' is not found in App\\User model.", 26],
            ["Relation 'missing' is not found in App\\User model.", 27],
            ["Relation 'missing' is not found in App\\User model.", 28],
            ["Relation 'missing' is not found in App\\User model.", 29],
            ["Relation 'missing' is not found in App\\Account model.", 30],
            ["Relation 'missing' is not found in App\\User model.", 31],
            ["Relation 'missing' is not found in App\\User model.", 32],
            ["Relation 'missing' is not found in App\\User model.", 33],
            ["Relation 'missing' is not found in App\\User model.", 34],
            ["Relation 'missing' is not found in App\\User model.", 35],
            ["Relation 'accounts.transactions' is not found in App\\User model.", 36],
            ["Relation 'accounts  as  total' is not found in App\\User model.", 37],
            ["Relation 'accounts as total' is not found in App\\User model.", 38],
            ["Relation 'missing' is not found in App\\User model.", 64],
            ["Relation 'missing' is not found in App\\User model.", 65],
            ["Relation 'missing' is not found in App\\User model.", 66],
            ["Relation 'missing' is not found in App\\User model.", 67],
            ["Relation 'missing' is not found in App\\User model.", 68],
            ["Relation 'missing' is not found in App\\User model.", 69],
            ["Relation 'missing' is not found in App\\User model.", 70],
            ["Relation 'missing' is not found in App\\User model.", 71],
            ["Relation 'missing' is not found in App\\User model.", 72],
            ["Relation 'missing' is not found in App\\User model.", 73],
            ["Relation 'missing' is not found in App\\User model.", 74],
            ["Relation 'missing' is not found in App\\User model.", 75],
            ["Relation 'missing' is not found in App\\User model.", 76],
            ["Relation 'missing' is not found in App\\User model.", 77],
            ["Relation 'missing' is not found in App\\User model.", 78],
            ["Relation 'missing' is not found in App\\User model.", 79],
            ["Relation 'missing' is not found in App\\User model.", 80],
            ["Relation 'missing' is not found in App\\User model.", 81],
            ["Relation 'missing' is not found in App\\User model.", 82],
            ["Relation 'missing' is not found in App\\User model.", 83],
            ["Relation 'missing' is not found in App\\User model.", 84],
            ["Relation 'missing' is not found in App\\User model.", 85],
            ["Relation 'missing' is not found in App\\User model.", 86],
            ["Relation 'save' is not found in App\\User model.", 87],
            ["Relation 'missing' is not found in App\\User model.", 112],
            ["Relation 'missing' is not found in App\\Comment model.", 119],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/phpstan-rules.neon',
        ];
    }
}

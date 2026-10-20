<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoMixedRequestInputInQueryRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use function sprintf;

/** @extends RuleTestCase<NoMixedRequestInputInQueryRule> */
class NoMixedRequestInputInQueryRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return self::getContainer()->getByType(NoMixedRequestInputInQueryRule::class);
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/no-mixed-request-input-in-query.php'], [
            [$this->message('string column email', 'where'), 30],
            [$this->message('string column email', 'where'), 31],
            [$this->message('string column email', 'orWhere'), 32],
            [$this->message('string column email', 'firstWhere'), 33],
            [$this->message('string column email', 'where'), 34],
            [$this->message('string column email', 'whereIn'), 35],
            [$this->message('string column users.email', 'where'), 36],
            [$this->message('string column email', 'where'), 37],
            [$this->message('string column email', 'where'), 38],
            [$this->message('string column email', 'where'), 39],
            [$this->message('string column email', 'where'), 40],
            [$this->message('string column email', 'where'), 41],
            [$this->message('string column email', 'where'), 42],
            [$this->message('string column email', 'where'), 43],
            [$this->message('string column email', 'where'), 44],
            [$this->message('string column email', 'where'), 45],
            [$this->message('string column email', 'where'), 46],
            [$this->message('string column email', 'where'), 47],
            [$this->message('string column enum_status', 'where'), 48],
            [$this->message('column backed_enum', 'where'), 49],
            [$this->message('string column slack_id', 'where'), 50],
            [$this->message('string column id', 'find'), 55],
            [$this->message('string column id', 'findOrFail'), 56],
            [$this->message('string column id', 'whereKey'), 57],
            [$this->message('column email of unknown type', 'where'), 62],
            [$this->message('column not_a_column of unknown type', 'where'), 63],
            [$this->message('a column of unknown type', 'where'), 64],
        ]);
    }

    private function message(string $column, string $method): string
    {
        return sprintf(
            "Mixed request input is compared with %s in %s(); a JSON body can send an integer, which MySQL compares with a string numerically.\n    💡 Cast it first, for example with \$request->string('key')->toString(). Validated input keeps the type the client sent.",
            $column,
            $method,
        );
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/phpstan-rules.neon'];
    }
}

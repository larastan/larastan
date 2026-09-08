<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\FormRequestUnknownValidatedKeyRule;
use Larastan\Larastan\Support\FormRequestHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/** @extends RuleTestCase<FormRequestUnknownValidatedKeyRule> */
class FormRequestUnknownValidatedKeyRuleTest extends RuleTestCase
{
    private bool $checkUnionTypes = true;

    protected function getRule(): Rule
    {
        return new FormRequestUnknownValidatedKeyRule(self::getContainer()->getByType(FormRequestHelper::class), $this->checkUnionTypes);
    }

    /** @return iterable<array{bool}> */
    public static function unionSettings(): iterable
    {
        yield [false];
        yield [true];
    }

    #[DataProvider('unionSettings')]
    public function testKnownPaths(bool $checkUnionTypes): void
    {
        $this->checkUnionTypes = $checkUnionTypes;
        $errors                = [];

        foreach (
            [
                [62, "'emali'"],
                [63, "'emali'"],
                [64, "'emali'"],
                [65, "'emali'"],
                [65, "'profile.emali'"],
                [66, "'emali'"],
                [67, "'emali'"],
                [67, "'profile.emali'"],
                [69, "'profile.emali'"],
                [70, "'email.0'"],
                [72, "'users.0.emali'"],
                [73, "'removed'"],
                [78, '1'],
                [80, "'00'"],
                [81, "''"],
                [82, "'profile..name'"],
                [85, "'emali'"],
                [117, "'emali'"],
                [118, "'emali'"],
            ] as [$line, $key]
        ) {
            $errors[] = $this->error($key, 'FormRequestUnknownKey\StoreRequest', $line);
        }

        $errors[] = $this->error("'emali'", 'FormRequestUnknownKey\InheritedRequest', 123);

        if ($checkUnionTypes) {
            $errors[] = $this->error("'email'", 'FormRequestUnknownKey\UpdateRequest', 140, true);
            $errors[] = $this->error("'email'", 'FormRequestUnknownKey\UpdateRequest', 141, true);
        }

        $errors[] = $this->error("'absent'", 'FormRequestUnknownKey\StoreRequest|FormRequestUnknownKey\UpdateRequest', 142);

        if ($checkUnionTypes) {
            $errors[] = $this->error("'absent'", 'FormRequestUnknownKey\StoreRequest', 143, true);
            $errors[] = $this->error("'absent'", 'FormRequestUnknownKey\StoreRequest', 144, true);
        }

        $errors[] = $this->error("'emali'", 'FormRequestUnknownKey\StoreRequest', 153);
        $errors[] = $this->error("'profile.emali'", 'FormRequestUnknownKey\OpenRootRequest', 183);

        $this->analyse([__DIR__ . '/data/form-request-unknown-validated-key.php'], $errors);
    }

    /** @return array{string, int, string} */
    private function error(string $key, string $request, int $line, bool $partial = false): array
    {
        return [
            'Key ' . $key . ' does not exist in validated data of ' . $request . '.',
            $line,
            ($partial ? 'Other possible request types may allow this key. ' : '') . "Check the key against the fields included by the request's validation rules.",
        ];
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

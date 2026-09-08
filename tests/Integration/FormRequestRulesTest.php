<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_map;
use function sort;

class FormRequestRulesTest extends PHPStanTestCase
{
    private static string $configuration = 'enabled';

    public static function setUpBeforeClass(): void
    {
        self::getContainer();
    }

    /** @return iterable<array{string, list<string>}> */
    public static function configurations(): iterable
    {
        $errors = [
            'larastan.formRequest.requiredNullable',
            'larastan.formRequest.requiredMissing',
            'larastan.formRequest.unknownValidatedKey',
            'larastan.formRequest.prematureValidatedAccess',
            'larastan.formRequest.prematureValidatedAccess',
        ];

        yield ['enabled', [...$errors, 'larastan.formRequest.unknownValidatedKey']];
        yield ['loose-unions', $errors];
        yield ['disabled', []];
    }

    /** @param list<string> $expected */
    #[DataProvider('configurations')]
    public function testFeatureGateAndUnionSetting(string $configuration, array $expected): void
    {
        self::$configuration = $configuration;

        try {
            $analyser = self::getContainer()->getByType(Analyser::class);
            $errors   = $analyser->analyse([__DIR__ . '/data/form-request-rules.php'], null, null, true, null)->getErrors();
            $actual   = array_map(static fn (Error $error): string|null => $error->getIdentifier(), $errors);
            sort($actual);
            sort($expected);

            $this->assertSame($expected, $actual);
        } finally {
            self::$configuration = 'enabled';
        }
    }

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/form-request-rules-' . self::$configuration . '.neon'];
    }
}

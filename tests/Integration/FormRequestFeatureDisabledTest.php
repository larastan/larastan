<?php

declare(strict_types=1);

namespace Tests\Integration;

use Larastan\Larastan\LarastanStubFilesExtension;
use PHPStan\Analyser\Analyser;
use PHPStan\Testing\PHPStanTestCase;

use function array_filter;
use function str_contains;

class FormRequestFeatureDisabledTest extends PHPStanTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::getContainer();
    }

    public function testFeatureIsDisabledByDefault(): void
    {
        $this->assertFalse(self::getContainer()->getParameter('checkFormRequestTypes'));

        /** @var Analyser $analyser */
        $analyser = self::getContainer()->getByType(Analyser::class); // @phpstan-ignore-line
        $file     = __DIR__ . '/data/form-request-feature-disabled.php';

        $this->assertSame([], $analyser->analyse([$file], null, null, true, null)->getErrors());

        $stubFiles = self::getContainer()->getByType(LarastanStubFilesExtension::class)->getFiles();

        $this->assertSame([], array_filter($stubFiles, static fn (string $stubFile): bool => str_contains($stubFile, '/stubs/formRequest/')));
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/form-request-feature-disabled.neon'];
    }
}

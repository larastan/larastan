<?php

declare(strict_types=1);

namespace Tests\Integration;

use Larastan\Larastan\LarastanStubFilesExtension;
use PHPStan\Analyser\Analyser;
use PHPStan\Testing\PHPStanTestCase;

use function array_filter;
use function str_ends_with;

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

        $stubFiles           = self::getContainer()->getByType(LarastanStubFilesExtension::class)->getFiles();
        $featureStubSuffixes = [
            '/Validation/AnyOf.stub',
            '/Validation/ArrayKeys.stub',
            '/Validation/Rules.stub',
            '/Validation/StringRule.stub',
        ];
        $loadedFeatureStubs  = array_filter(
            $stubFiles,
            static function (string $stubFile) use ($featureStubSuffixes): bool {
                foreach ($featureStubSuffixes as $suffix) {
                    if (str_ends_with($stubFile, $suffix)) {
                        return true;
                    }
                }

                return false;
            },
        );

        $this->assertSame([], $loadedFeatureStubs);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/form-request-feature-disabled.neon'];
    }
}

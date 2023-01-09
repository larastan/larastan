<?php

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;

class IntegrationTest extends PHPStanTestCase
{
    /**
     * @return iterable<mixed>
     */
    public function dataIntegrationTests(): iterable
    {
        yield [__DIR__.'/integration/data/test-case-extension.php'];
        yield [__DIR__.'/integration/data/model-properties.php'];
        yield [__DIR__.'/integration/data/blade-view.php'];
    }

    /**
     * @dataProvider dataIntegrationTests
     */
    public function testIntegration(string $file, ?array $expectedErrors = null): void
    {
        $errors = $this->runAnalyse($file);

        if ($expectedErrors === null) {
            $this->assertNoErrors($errors);
        } else {
            // TODO: compare errors
        }
    }

    /**
     * @see https://github.com/phpstan/phpstan-src/blob/c9772621c0bd6eab7e02fdaa03714bea239b372d/tests/PHPStan/Analyser/AnalyserIntegrationTest.php#L604-L622
     * @see https://github.com/phpstan/phpstan/discussions/6888#discussioncomment-2423613
     *
     * @param  string[]|null  $allAnalysedFiles
     * @return Error[]
     */
    private function runAnalyse(string $file, ?array $allAnalysedFiles = null): array
    {
        $file = $this->getFileHelper()->normalizePath($file);

        /** @var Analyser $analyser */
        $analyser = self::getContainer()->getByType(Analyser::class); // @phpstan-ignore-line

        /** @var FileHelper $fileHelper */
        $fileHelper = self::getContainer()->getByType(FileHelper::class);

        $errors = $analyser->analyse([$file], null, null, true, $allAnalysedFiles)->getErrors(); // @phpstan-ignore-line

        foreach ($errors as $error) {
            $this->assertSame($fileHelper->normalizePath($file), $error->getFilePath());
        }

        return $errors;
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/phpstan-tests.neon'];
    }
}

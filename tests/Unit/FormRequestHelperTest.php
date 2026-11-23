<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\SafeReturnRequest;
use App\Traits\FormRequestRules\ProvidesRules;
use Larastan\Larastan\Support\FormRequestHelper;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\ResultCache\FileResultCacheValueExtension;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\File\FileHelper;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

use function sprintf;

#[CoversClass(FormRequestHelper::class)]
class FormRequestHelperTest extends PHPStanTestCase
{
    #[Test]
    public function it_tracks_the_file_declaring_the_rules_for_files_using_a_form_request(): void
    {
        $container = self::getContainer();
        $collector = $container->getByType(ValueDependencyCollector::class);
        $file      = $container->getByType(FileHelper::class)->normalizePath(__DIR__ . '/data/form-request-rules-dependency.php');
        $ruleFiles = [(new ReflectionClass(SafeReturnRequest::class))->getFileName(), (new ReflectionClass(ProvidesRules::class))->getFileName()];

        // The second run reads the shapes the first one remembered and must still depend on the files.
        foreach ([1, 2] as $run) {
            $result = $container->getByType(FileAnalyser::class)->analyseFile(
                $file,
                [$file => true],
                new DirectRuleRegistry([]),
                new CollectorRegistry([]),
                null,
            );

            self::assertSame([], $result->getErrors());

            foreach ($ruleFiles as $ruleFile) {
                self::assertIsString($ruleFile);
                self::assertContains(
                    ValueDependencyCollector::getId(FileResultCacheValueExtension::class, $collector->getFileKey($ruleFile)),
                    $result->getValueDependencies()['dependents'][$file]['analysis'],
                    sprintf('Run %d', $run),
                );
            }
        }
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../Type/data/config-with-migrations.neon'];
    }
}

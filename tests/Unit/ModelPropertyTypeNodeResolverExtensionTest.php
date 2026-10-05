<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\MigrationHelper;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\ResultCache\DirectoryResultCacheValueExtension;
use PHPStan\Analyser\ValueDependencyCollector;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\File\FileHelper;
use PHPStan\Rules\DirectRegistry as DirectRuleRegistry;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\Test;

class ModelPropertyTypeNodeResolverExtensionTest extends PHPStanTestCase
{
    #[Test]
    public function it_tracks_the_schema_directories_for_callers_of_a_method_with_a_model_property_parameter(): void
    {
        $container = self::getContainer();
        $collector = $container->getByType(ValueDependencyCollector::class);
        $file      = $container->getByType(FileHelper::class)->normalizePath(__DIR__ . '/data/model-property-schema-dependency.php');

        $result = $container->getByType(FileAnalyser::class)->analyseFile(
            $file,
            [$file => true],
            new DirectRuleRegistry([]),
            new CollectorRegistry([]),
            null,
        );

        self::assertSame([], $result->getErrors());

        $migrationDirectories = $container->getByType(MigrationHelper::class)->getMigrationDirectories();

        self::assertCount(1, $migrationDirectories);
        self::assertContains(
            ValueDependencyCollector::getId(DirectoryResultCacheValueExtension::class, $collector->getDirectoryKey($migrationDirectories[0], '*.php')),
            $result->getValueDependencies()['dependents'][$file]['analysis'],
        );
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../Type/data/config-check-model-properties.neon'];
    }
}

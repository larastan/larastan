<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Properties\MigrationCache;
use Larastan\Larastan\Properties\MigrationHelper;
use Larastan\Larastan\Properties\ModelCastHelper;
use Larastan\Larastan\Properties\ModelPropertyHelper;
use Larastan\Larastan\Properties\Schema\MySqlDataTypeToPhpTypeConverter;
use Larastan\Larastan\Properties\SquashedMigrationHelper;
use PHPStan\Analyser\DeclarationDependencyTracker;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\File\FileHelper;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

use function sys_get_temp_dir;

#[CoversClass(ModelPropertyHelper::class)]
class ModelPropertyHelperDependencyTrackingTest extends PHPStanTestCase
{
    private ReflectionProvider $reflectionProvider;

    private FileHelper $fileHelper;

    /** @var DeclarationDependencyTracker&object{tracked: list<array{string, string, string}>} */
    private DeclarationDependencyTracker $dependencyTracker;

    public function setUp(): void
    {
        $this->reflectionProvider = $this->createReflectionProvider();
        $this->fileHelper         = self::getContainer()->getByType(FileHelper::class);
        $this->dependencyTracker  = new class implements DeclarationDependencyTracker {
            /** @var list<array{string, string, string}> */
            public array $tracked = [];

            public function trackValueDependency(ClassReflection $classReflection, string $extensionClass, string $key): void
            {
                throw new RuntimeException('Unexpected value dependency.');
            }

            public function trackFileDependency(ClassReflection $classReflection, string $file): void
            {
                throw new RuntimeException('Unexpected file dependency.');
            }

            public function trackDirectoryDependency(ClassReflection $classReflection, string $directory, string $pattern = '*'): void
            {
                $this->tracked[] = [$classReflection->getName(), $directory, $pattern];
            }

            public function trackClassDependency(ClassReflection $classReflection, string $className): void
            {
                throw new RuntimeException('Unexpected class dependency.');
            }
        };
    }

    #[Test]
    public function it_tracks_migration_and_schema_directories_as_model_dependencies(): void
    {
        $missingSchemaPath   = __DIR__ . '/data/missing_schema';
        $modelPropertyHelper = $this->buildModelPropertyHelper(
            [__DIR__ . '/data/basic_migration'],
            [__DIR__ . '/data/schema', $missingSchemaPath],
        );

        self::assertTrue($modelPropertyHelper->hasDatabaseProperty($this->reflectionProvider->getClass(User::class), 'email'));

        // Recorded on Model, so that every file depending on any model is invalidated.
        self::assertSame([
            [Model::class, $this->fileHelper->absolutizePath(__DIR__ . '/data/basic_migration'), '*.[pP][hH][pP]'],
            [Model::class, $this->fileHelper->absolutizePath(__DIR__ . '/data/schema'), '*'],
            // A schema directory that does not exist yet is tracked so that creating it is noticed.
            [Model::class, $this->fileHelper->absolutizePath($missingSchemaPath), '*'],
        ], $this->dependencyTracker->tracked);
    }

    #[Test]
    public function it_tracks_the_directories_a_glob_matches(): void
    {
        $modelPropertyHelper = $this->buildModelPropertyHelper([__DIR__ . '/data/basic_migr*'], []);

        $modelPropertyHelper->hasDatabaseProperty('users', 'email');

        self::assertSame(
            [Model::class, $this->fileHelper->absolutizePath(__DIR__ . '/data/basic_migration'), '*.[pP][hH][pP]'],
            $this->dependencyTracker->tracked[0],
        );
    }

    #[Test]
    public function it_tracks_directories_for_table_name_lookups(): void
    {
        $modelPropertyHelper = $this->buildModelPropertyHelper([__DIR__ . '/data/basic_migration'], []);

        self::assertTrue($modelPropertyHelper->hasDatabaseProperty('users', 'email'));
        self::assertContains(
            [Model::class, $this->fileHelper->absolutizePath(__DIR__ . '/data/basic_migration'), '*.[pP][hH][pP]'],
            $this->dependencyTracker->tracked,
        );
    }

    #[Test]
    public function it_does_not_track_directories_when_scanning_is_disabled(): void
    {
        $modelPropertyHelper = $this->buildModelPropertyHelper(
            [__DIR__ . '/data/basic_migration'],
            [__DIR__ . '/data/schema'],
            true,
        );

        $modelPropertyHelper->hasDatabaseProperty($this->reflectionProvider->getClass(User::class), 'email');

        self::assertSame([], $this->dependencyTracker->tracked);
    }

    /**
     * @param string[] $migrationPaths
     * @param string[] $schemaPaths
     */
    private function buildModelPropertyHelper(array $migrationPaths, array $schemaPaths, bool $disableScan = false): ModelPropertyHelper
    {
        $parser = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');

        return new ModelPropertyHelper(
            self::getContainer()->getByType(TypeStringResolver::class),
            new MigrationHelper(
                $parser,
                $migrationPaths,
                $this->fileHelper,
                $disableScan,
                $this->reflectionProvider,
                self::getContainer()->getByType(InitializerExprTypeResolver::class),
            ),
            new SquashedMigrationHelper(
                $schemaPaths,
                $this->fileHelper,
                new MySqlDataTypeToPhpTypeConverter(),
                self::getContainer()->getService('sqlParser'),
                $disableScan,
            ),
            new ModelCastHelper(
                $this->reflectionProvider,
                $parser,
                false,
                self::getContainer()->getByType(ScopeFactory::class),
            ),
            new MigrationCache(sys_get_temp_dir(), false),
            $this->dependencyTracker,
            $this->reflectionProvider,
        );
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Member;
use Larastan\Larastan\Properties\MigrationCache;
use Larastan\Larastan\Properties\MigrationHelper;
use Larastan\Larastan\Properties\ModelCastHelper;
use Larastan\Larastan\Properties\ModelPropertyHelper;
use Larastan\Larastan\Properties\Schema\MySqlDataTypeToPhpTypeConverter;
use Larastan\Larastan\Properties\SquashedMigrationHelper;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\File\FileHelper;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;

use function class_exists;
use function sys_get_temp_dir;

#[CoversClass(ModelPropertyHelper::class)]
class ModelPropertyHelperTest extends PHPStanTestCase
{
    private ReflectionProvider $reflectionProvider;

    private Parser $parser;

    private FileHelper $fileHelper;

    public function setUp(): void
    {
        if (! class_exists('Illuminate\Database\Eloquent\Attributes\Table')) {
            $this->markTestSkipped('Eloquent PHP attributes require Laravel 13+.');
        }

        $this->reflectionProvider = $this->createReflectionProvider();
        $this->parser             = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $this->fileHelper         = self::getContainer()->getByType(FileHelper::class);
    }

    #[Test]
    public function it_applies_table_php_attribute_after_initialize_model_attributes(): void
    {
        $reflection = new ReflectionClass(Member::class);
        $instance   = $reflection->newInstanceWithoutConstructor();

        // Without initializeModelAttributes() the #[Table] attribute is ignored and Laravel
        // derives the table name from the class name instead.
        self::assertNotSame('users', $instance->getTable());

        $instance->initializeModelAttributes();

        self::assertSame('users', $instance->getTable());
    }

    #[Test]
    public function it_uses_custom_table_name_from_table_php_attribute_when_checking_database_property(): void
    {
        $modelPropertyHelper = $this->buildModelPropertyHelper(
            [__DIR__ . '/data/basic_migration'],
        );

        $classReflection = $this->reflectionProvider->getClass(Member::class);

        // The model uses #[Table(name: 'users')], not the default 'members' derived from
        // the class name. The fix ensures initializeModelAttributes() is called so the correct
        // table is resolved and columns from the users table are found.
        self::assertTrue($modelPropertyHelper->hasDatabaseProperty($classReflection, 'email'));
        self::assertFalse($modelPropertyHelper->hasDatabaseProperty($classReflection, 'nonexistent_column'));
    }

    /** @param string[] $migrationPaths */
    private function buildModelPropertyHelper(array $migrationPaths): ModelPropertyHelper
    {
        $migrationHelper = new MigrationHelper(
            $this->parser,
            $migrationPaths,
            $this->fileHelper,
            false,
            $this->reflectionProvider,
        );

        $squashedMigrationHelper = new SquashedMigrationHelper(
            [],
            $this->fileHelper,
            new MySqlDataTypeToPhpTypeConverter(),
            self::getContainer()->getService('sqlParser'),
            false,
        );

        $modelCastHelper = new ModelCastHelper(
            $this->reflectionProvider,
            $this->parser,
            false,
            self::getContainer()->getByType(ScopeFactory::class),
        );

        return new ModelPropertyHelper(
            self::getContainer()->getByType(TypeStringResolver::class),
            $migrationHelper,
            $squashedMigrationHelper,
            $modelCastHelper,
            new MigrationCache(sys_get_temp_dir(), false),
        );
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

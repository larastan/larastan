<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\SchemaAggregator;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

use function array_keys;
use function sprintf;

use const PHP_VERSION_ID;

class SchemaAggregatorTest extends PHPStanTestCase
{
    /** @return iterable<string, array{string}> */
    public static function tableConstants(): iterable
    {
        yield 'untyped' => ['Constants::USERS'];
        yield 'PHPDoc type' => ['Constants::DOCUMENTED_USERS'];
        yield 'inherited initializer' => ['InheritedConstants::DOCUMENTED_USERS'];

        if (PHP_VERSION_ID < 80300) {
            return;
        }

        yield 'native type' => ['TypedConstants::USERS'];
        yield 'typed expression' => ['TypedConstants::CONCATENATED_USERS'];
    }

    #[Test]
    #[DataProvider('tableConstants')]
    public function it_resolves_table_constant_initializers(string $constant): void
    {
        $parser     = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $aggregator = new SchemaAggregator(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(InitializerExprTypeResolver::class),
        );
        $statements = $parser->parseString(sprintf(<<<'PHP'
<?php

namespace Tests\Unit\SchemaAggregatorConstants;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable
{
    public function up(): void
    {
        Schema::create(%1$s, function (Blueprint $table) {
            $table->id();
        });

        Schema::table(%1$s, function (Blueprint $table) {
            $table->string('email')->nullable();
        });
    }
}
PHP, $constant));

        $aggregator->addStatements($statements);

        self::assertArrayHasKey('users', $aggregator->tables);
        self::assertSame(['id', 'email'], array_keys($aggregator->tables['users']->columns));
        self::assertSame('string', $aggregator->tables['users']->columns['email']->readableType);
        self::assertTrue($aggregator->tables['users']->columns['email']->nullable);
    }

    /** @return iterable<string, array{string}> */
    public static function indexMethodCalls(): iterable
    {
        yield 'fullText' => ["\$table->fullText('body');"];
        yield 'dropFullText' => ["\$table->dropFullText('posts_body_fulltext');"];
        yield 'spatialIndex' => ["\$table->spatialIndex('location');"];
        yield 'dropSpatialIndex' => ["\$table->dropSpatialIndex('posts_location_spatialindex');"];
        yield 'vectorIndex' => ["\$table->vectorIndex('embedding');"];
        yield 'rawIndex' => ["\$table->rawIndex('lower(body)', 'posts_body_lower_index');"];
    }

    #[Test]
    #[DataProvider('indexMethodCalls')]
    public function it_keeps_columns_unchanged_by_index_methods(string $indexMethodCall): void
    {
        $parser     = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $aggregator = new SchemaAggregator(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(InitializerExprTypeResolver::class),
        );
        $statements = $parser->parseString(sprintf(<<<'PHP'
<?php

namespace Tests\Unit\SchemaAggregatorIndexes;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('body')->nullable();
            $table->point('location')->nullable();
            $table->vector('embedding', 3)->nullable();
        });

        Schema::table('posts', function (Blueprint $table) {
            %1$s
        });
    }
}
PHP, $indexMethodCall));

        $aggregator->addStatements($statements);

        $columns = [];

        foreach ($aggregator->tables['posts']->columns as $name => $column) {
            $columns[$name] = [$column->readableType, $column->nullable];
        }

        self::assertSame([
            'id' => ['non-negative-int', false],
            'body' => ['string', true],
            'location' => ['mixed', true],
            'embedding' => ['mixed', true],
        ], $columns);
    }
}

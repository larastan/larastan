<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\SchemaAggregator;
use Larastan\Larastan\SQL\IamcalSqlParser;
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
            new IamcalSqlParser(),
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

    #[Test]
    public function it_applies_create_table_sql_from_statement_calls(): void
    {
        $aggregator = $this->aggregateMigration(<<<'PHP'
            DB::statement('CREATE TABLE `users` (
                `id` int unsigned NOT NULL,
                `email` varchar(255) DEFAULT NULL
            )');

            Schema::table('users', function (Blueprint $table) {
                $table->string('name');
            });

            DB::connection('mysql')->statement('CREATE TABLE `reports` (`id` int unsigned NOT NULL)');

            \Illuminate\Support\Facades\DB::unprepared(<<<'SQL'
                CREATE TABLE `posts` (`id` int NOT NULL);
                CREATE TABLE `comments` (`id` int NOT NULL, `body` varchar(255) NULL);
            SQL);
        PHP);

        self::assertSame(['id', 'email', 'name'], array_keys($aggregator->tables['users']->columns));
        self::assertSame('non-negative-int', $aggregator->tables['users']->columns['id']->readableType);
        self::assertFalse($aggregator->tables['users']->columns['id']->nullable);
        self::assertSame('string', $aggregator->tables['users']->columns['email']->readableType);
        self::assertTrue($aggregator->tables['users']->columns['email']->nullable);
        self::assertSame('string', $aggregator->tables['users']->columns['name']->readableType);

        self::assertSame(['id'], array_keys($aggregator->tables['reports']->columns));
        self::assertSame('non-negative-int', $aggregator->tables['reports']->columns['id']->readableType);
        self::assertFalse($aggregator->tables['reports']->columns['id']->nullable);

        self::assertSame(['id'], array_keys($aggregator->tables['posts']->columns));
        self::assertSame('int', $aggregator->tables['posts']->columns['id']->readableType);
        self::assertSame(['id', 'body'], array_keys($aggregator->tables['comments']->columns));
        self::assertTrue($aggregator->tables['comments']->columns['body']->nullable);
    }

    #[Test]
    public function it_ignores_sql_the_parser_does_not_apply(): void
    {
        $aggregator = $this->aggregateMigration(<<<'PHP'
            Schema::create('users', function (Blueprint $table) {
                $table->string('name');
            });

            DB::statement('ALTER TABLE users RENAME COLUMN name TO full_name');
            DB::statement('ALTER TABLE public.etapas_censo SET SCHEMA censo');
            DB::statement('INSERT INTO users (name) VALUES (1)');
            DB::unprepared('CREATE SCHEMA IF NOT EXISTS censo');
            DB::statement('CREATE TABLE `users` (`id` int unsigned NOT NULL)');

            $sql = 'CREATE TABLE `skipped` (`id` int NOT NULL)';
            DB::statement($sql);
        PHP);

        self::assertSame(['users'], array_keys($aggregator->tables));
        self::assertArrayHasKey('name', $aggregator->tables['users']->columns);
        self::assertArrayNotHasKey('id', $aggregator->tables['users']->columns);
        self::assertArrayNotHasKey('full_name', $aggregator->tables['users']->columns);
    }

    #[Test]
    public function it_keeps_columns_recorded_by_the_schema_builder(): void
    {
        $aggregator = $this->aggregateMigration(<<<'PHP'
            Schema::create('users', function (Blueprint $table) {
                $table->string('name');
            });

            DB::statement('CREATE TABLE `users` (`id` int NOT NULL)');
        PHP);

        self::assertSame(['name'], array_keys($aggregator->tables['users']->columns));
        self::assertSame('string', $aggregator->tables['users']->columns['name']->readableType);
    }

    #[Test]
    public function it_ignores_sql_the_parser_rejects(): void
    {
        $aggregator = $this->aggregateMigration(<<<'PHP'
            Schema::create('users', function (Blueprint $table) {
                $table->string('name');
            });

            DB::statement('CREATE TABLE `broken (id INT)');
        PHP);

        self::assertSame(['name'], array_keys($aggregator->tables['users']->columns));
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
            new IamcalSqlParser(),
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

    #[Test]
    public function it_resolves_column_name_constants(): void
    {
        $parser     = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $aggregator = new SchemaAggregator(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(InitializerExprTypeResolver::class),
            new IamcalSqlParser(),
        );
        $statements = $parser->parseString(<<<'PHP'
<?php

namespace Tests\Unit\SchemaAggregatorConstants;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompensationsTable
{
    public function up(): void
    {
        Schema::create('compensations', function (Blueprint $table) {
            $table->id();
            $table->decimal(Constants::AMOUNT, 10, 2)->nullable();
            $table->string(Constants::MISSING);
            $table->string(Constants::class);
        });
    }
}
PHP);

        $aggregator->addStatements($statements);

        self::assertSame(['id', 'amount'], array_keys($aggregator->tables['compensations']->columns));
        self::assertSame('float', $aggregator->tables['compensations']->columns['amount']->readableType);
        self::assertTrue($aggregator->tables['compensations']->columns['amount']->nullable);
    }

    /** @return iterable<string, array{string}> */
    public static function enumCases(): iterable
    {
        yield 'pure' => ['PureStatus::Draft'];
        yield 'backed' => ['BackedStatus::Draft'];
    }

    #[Test]
    #[DataProvider('enumCases')]
    public function it_skips_enum_cases_as_table_and_column_names(string $case): void
    {
        $parser     = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $aggregator = new SchemaAggregator(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(InitializerExprTypeResolver::class),
            new IamcalSqlParser(),
        );
        $statements = $parser->parseString(sprintf(<<<'PHP'
<?php

namespace Tests\Unit\SchemaAggregatorConstants;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable
{
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string(%1$s);
        });

        Schema::create(%1$s, function (Blueprint $table) {
            $table->id();
        });
    }
}
PHP, $case));

        $aggregator->addStatements($statements);

        self::assertSame(['statuses'], array_keys($aggregator->tables));
        self::assertSame(['id'], array_keys($aggregator->tables['statuses']->columns));
    }

    private function aggregateMigration(string $body): SchemaAggregator
    {
        $parser     = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $aggregator = new SchemaAggregator(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(InitializerExprTypeResolver::class),
            new IamcalSqlParser(),
        );

        $aggregator->addStatements($parser->parseString(<<<PHP
            <?php

            namespace Tests\Unit\SchemaAggregatorConstants;

            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\DB;
            use Illuminate\Support\Facades\Schema;

            class MoveTable
            {
                public function up(): void
                {
            $body
                }
            }
            PHP));

        return $aggregator;
    }
}

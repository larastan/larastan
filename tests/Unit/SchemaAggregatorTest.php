<?php

declare(strict_types=1);

namespace Tests\Unit;

use Larastan\Larastan\Properties\SchemaAggregator;
use Larastan\Larastan\Properties\SchemaTable;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

use function array_keys;
use function array_map;
use function sprintf;
use function str_contains;

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

    /** @return iterable<string, array{string|null, list<string>, array<string, list<string>>}> */
    public static function connectionMigrations(): iterable
    {
        yield 'table moved to another connection, then dropped from the default one' => [
            'mysql',
            [
                'Schema::create("agreements", function (Blueprint $table) { $table->id(); $table->uuid("uuid"); });',
                'Schema::connection("agreements")->create("agreements", function (Blueprint $table) { $table->id(); $table->uuid("uuid"); $table->string("status"); });',
                'Schema::dropIfExists("agreements");',
            ],
            ['agreements' => ['id', 'uuid', 'status']],
        ];

        yield 'drop on the same named connection' => [
            'mysql',
            [
                'Schema::connection("foo")->create("users", function (Blueprint $table) { $table->id(); });',
                'Schema::connection("foo")->dropIfExists("users");',
            ],
            [],
        ];

        yield 'default connection named explicitly' => [
            'mysql',
            [
                'Schema::connection("mysql")->create("users", function (Blueprint $table) { $table->id(); });',
                'Schema::dropIfExists("users");',
            ],
            [],
        ];

        yield 'alter on another connection' => [
            'mysql',
            [
                'Schema::connection("foo")->create("users", function (Blueprint $table) { $table->id(); });',
                'Schema::table("users", function (Blueprint $table) { $table->string("name"); });',
            ],
            ['users' => ['id']],
        ];

        yield 'rename on another connection' => [
            'mysql',
            [
                'Schema::connection("foo")->create("users", function (Blueprint $table) { $table->id(); });',
                'Schema::rename("users", "members");',
            ],
            ['users' => ['id']],
        ];

        yield 'connection set on the migration class' => [
            'mysql',
            [
                'protected $connection = "foo"; public function up(): void { Schema::create("users", function (Blueprint $table) { $table->id(); }); }',
                'Schema::dropIfExists("users");',
            ],
            ['users' => ['id']],
        ];

        yield 'connection that cannot be resolved statically' => [
            'mysql',
            [
                'Schema::connection("foo")->create("users", function (Blueprint $table) { $table->id(); });',
                'Schema::connection($this->getConnection())->dropIfExists("users");',
            ],
            [],
        ];

        yield 'unknown default connection' => [
            null,
            [
                'Schema::connection("foo")->create("users", function (Blueprint $table) { $table->id(); });',
                'Schema::dropIfExists("users");',
            ],
            [],
        ];
    }

    /**
     * @param list<string>                $migrations
     * @param array<string, list<string>> $expected
     */
    #[Test]
    #[DataProvider('connectionMigrations')]
    public function it_only_applies_schema_changes_to_tables_on_the_same_connection(
        string|null $defaultConnection,
        array $migrations,
        array $expected,
    ): void {
        $parser     = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
        $aggregator = new SchemaAggregator(
            $this->createReflectionProvider(),
            self::getContainer()->getByType(InitializerExprTypeResolver::class),
            [],
            $defaultConnection,
        );

        foreach ($migrations as $migration) {
            if (! str_contains($migration, 'function up()')) {
                $migration = sprintf('public function up(): void { %s }', $migration);
            }

            $aggregator->addStatements($parser->parseString(sprintf(<<<'PHP'
<?php

namespace Tests\Unit\SchemaAggregatorConnections;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    %s
};
PHP, $migration)));
        }

        self::assertSame(
            $expected,
            array_map(static fn (SchemaTable $table): array => array_keys($table->columns), $aggregator->tables),
        );
    }
}

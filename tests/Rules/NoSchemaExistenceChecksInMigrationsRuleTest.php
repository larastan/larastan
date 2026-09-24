<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Application;
use Larastan\Larastan\Rules\NoSchemaExistenceChecksInMigrationsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\Test;

/** @extends RuleTestCase<NoSchemaExistenceChecksInMigrationsRule> */
class NoSchemaExistenceChecksInMigrationsRuleTest extends RuleTestCase
{
    /** @var list<non-empty-string> */
    private array $migrationDirectories = [];

    private string|null $originalDatabasePath = null;

    protected function tearDown(): void
    {
        if ($this->originalDatabasePath !== null) {
            Application::getInstance()->useDatabasePath($this->originalDatabasePath);
            $this->originalDatabasePath = null;
        }

        parent::tearDown();
    }

    protected function getRule(): Rule
    {
        return new NoSchemaExistenceChecksInMigrationsRule($this->migrationDirectories, $this->getFileHelper());
    }

    #[Test]
    public function itReportsSchemaExistenceChecksInsideMigrations(): void
    {
        $this->migrationDirectories = [__DIR__ . '/data/migrations'];

        $this->analyse([__DIR__ . '/data/migrations/2024_01_01_000000_create_users_table.php'], [
            ["Called 'Schema::hasTable()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 13],
            ["Called 'Schema::hasColumn()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 19],
            ["Called 'Schema::hasColumns()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 23],
            ["Called 'Schema::hasTable()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 27],
            ["Called 'Schema::hasColumn()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 33],
            ["Called 'Schema::hasTable()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 40],
        ]);
    }

    #[Test]
    public function itDoesNotReportMigrationsWithoutExistenceChecks(): void
    {
        $this->migrationDirectories = [__DIR__ . '/data/migrations'];

        $this->analyse([__DIR__ . '/data/migrations/2024_01_02_000000_create_posts_table.php'], []);
    }

    #[Test]
    public function itDoesNotReportSchemaExistenceChecksOutsideOfMigrations(): void
    {
        $this->migrationDirectories = [__DIR__ . '/data/migrations'];

        $this->analyse([__DIR__ . '/data/schema-existence-checks.php'], []);
    }

    #[Test]
    public function itReportsSchemaExistenceChecksInsideGlobMigrationDirectories(): void
    {
        $this->migrationDirectories = [__DIR__ . '/data/module/*/migrations'];

        $this->analyse([
            __DIR__ . '/data/module/foo/migrations/2024_01_01_000000_add_flag_to_users_table.php',
            __DIR__ . '/data/module/bar/migrations/2024_01_01_000000_add_flag_to_posts_table.php',
        ], [
            ["Called 'Schema::hasColumn()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 13],
            ["Called 'Schema::hasTable()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 13],
        ]);
    }

    #[Test]
    public function itFallsBackToTheApplicationMigrationsDirectory(): void
    {
        $this->overrideDatabasePath(__DIR__ . '/data/database');

        $this->migrationDirectories = [];

        $this->analyse([__DIR__ . '/data/database/migrations/2024_01_01_000000_create_comments_table.php'], [
            ["Called 'Schema::hasTable()' inside a migration. A migration runs against a known schema state, remove the conditional check.", 13],
        ]);
    }

    protected function overrideDatabasePath(string $path): void
    {
        $app = Application::getInstance();

        $this->originalDatabasePath = $app->databasePath();

        $app->useDatabasePath($path);
    }
}

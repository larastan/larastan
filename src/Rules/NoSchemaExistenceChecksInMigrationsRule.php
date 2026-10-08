<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Schema;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function count;
use function database_path;
use function glob;
use function in_array;
use function is_dir;
use function sprintf;
use function str_starts_with;

/**
 * Catches schema existence checks, like `Schema::hasTable()` and `Schema::hasColumn()`,
 * inside the migration directories.
 *
 * A migration always runs against a known schema state, so guarding it with an
 * existence check hides the real problem instead of solving it.
 *
 * @implements Rule<CallLike>
 */
class NoSchemaExistenceChecksInMigrationsRule implements Rule
{
    private const EXISTENCE_CHECKS = ['hasTable', 'hasColumn', 'hasColumns'];

    /** @var list<string>|null */
    private array|null $absoluteMigrationDirectories = null;

    /** @param list<non-empty-string> $migrationDirectories */
    public function __construct(private array $migrationDirectories, private FileHelper $fileHelper)
    {
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /**
     * @param CallLike $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $methodName = $this->getExistenceCheckName($node, $scope);

        if ($methodName === null) {
            return [];
        }

        if (! $this->isInsideMigrations($scope->getFile())) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Called 'Schema::%s()' inside a migration. A migration runs against a known schema state, remove the conditional check.",
                $methodName,
            ))
                ->identifier('larastan.noSchemaExistenceChecksInMigrations')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /** @return value-of<self::EXISTENCE_CHECKS>|null */
    private function getExistenceCheckName(CallLike $node, Scope $scope): string|null
    {
        if ($node instanceof StaticCall) {
            if (! $node->class instanceof Name) {
                return null;
            }

            $calledOn = new ObjectType($scope->resolveName($node->class));
            $expected = new ObjectType(Schema::class);
        } elseif ($node instanceof MethodCall) {
            $calledOn = $scope->getType($node->var);
            $expected = new ObjectType(Builder::class);
        } else {
            return null;
        }

        if (! $this->isSchema($expected, $calledOn)) {
            return null;
        }

        if (! $node->name instanceof Identifier) {
            return null;
        }

        $methodName = $node->name->toString();

        if (! in_array($methodName, self::EXISTENCE_CHECKS, true)) {
            return null;
        }

        return $methodName;
    }

    private function isSchema(ObjectType $expected, Type $calledOn): bool
    {
        return $expected->isSuperTypeOf($calledOn)->yes();
    }

    private function isInsideMigrations(string $file): bool
    {
        foreach ($this->getMigrationDirectories() as $migrationDirectory) {
            if (str_starts_with($file, $migrationDirectory)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private function getMigrationDirectories(): array
    {
        if ($this->absoluteMigrationDirectories !== null) {
            return $this->absoluteMigrationDirectories;
        }

        $directoryGlobs = $this->migrationDirectories;

        if (count($directoryGlobs) === 0) {
            $directoryGlobs = [database_path('migrations')]; // @phpstan-ignore-line
        }

        $this->absoluteMigrationDirectories = [];

        foreach ($directoryGlobs as $directoryGlob) {
            foreach ((glob($this->fileHelper->normalizePath($directoryGlob)) ?: []) as $directory) {
                $absolutePath = $this->fileHelper->absolutizePath($directory);

                if (! is_dir($absolutePath)) {
                    continue;
                }

                $this->absoluteMigrationDirectories[] = $absolutePath;
            }
        }

        return $this->absoluteMigrationDirectories;
    }
}

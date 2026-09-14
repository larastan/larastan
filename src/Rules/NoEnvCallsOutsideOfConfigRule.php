<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function config_path;
use function count;
use function glob;
use function is_array;
use function is_dir;
use function is_string;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Catches `env()` calls outside of the config directory.
 *
 * @implements Rule<FuncCall>
 */
class NoEnvCallsOutsideOfConfigRule implements Rule
{
    use HasContainer;

    /** @var list<string> */
    private array $configDirectories = [];

    private string|null $editorTmpFile;

    private string|null $editorInsteadOfFile;

    /** @param  list<non-empty-string> $configDirectories */
    public function __construct(array $configDirectories, private FileHelper $fileHelper)
    {
        if (count($configDirectories) !== 0) {
            foreach ($configDirectories as $directory) {
                $this->configDirectories[] = $this->fileHelper->normalizePath($directory);
            }
        } else {
            $this->configDirectories = [config_path()]; // @phpstan-ignore-line
        }

        $this->editorTmpFile       = $this->resolveCliPath('tmp-file');
        $this->editorInsteadOfFile = $this->resolveCliPath('instead-of');
    }

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @return array<int, RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->name;

        if (! $name instanceof Name) {
            return [];
        }

        if ($scope->resolveName($name) !== 'env') {
            return [];
        }

        $file = $this->resolveAnalysedFile($scope->getFile());

        if ($file === null) {
            return [];
        }

        if (! $this->isCalledOutsideOfConfig($file)) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.")
                ->identifier('larastan.noEnvCallsOutsideOfConfig')
                ->line($node->getStartLine())
                ->file($scope->getFile(), $scope->getFileDescription())
                ->build(),
        ];
    }

    protected function isCalledOutsideOfConfig(string $file): bool
    {
        foreach ($this->configDirectories as $configDirectoryGlob) {
            foreach ((glob($configDirectoryGlob) ?: []) as $configDirectory) {
                $absolutePath = $this->fileHelper->absolutizePath($configDirectory);

                if (! is_dir($absolutePath)) {
                    continue;
                }

                if (str_starts_with($file, $absolutePath)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * In editor mode (--tmp-file/--instead-of) PHPStan analyses a buffer file but reports
     * errors against the original path. The rewrite happens post-analysis, so `$scope->getFile()`
     * still points at the buffer here. Swap it to --instead-of so the config-dir check works;
     * if --instead-of is missing, return null to suppress the rule for this file.
     */
    private function resolveAnalysedFile(string $scopeFile): string|null
    {
        if ($this->editorTmpFile === null) {
            return $scopeFile;
        }

        if ($this->fileHelper->normalizePath($scopeFile) !== $this->editorTmpFile) {
            return $scopeFile;
        }

        return $this->editorInsteadOfFile;
    }

    private function resolveCliPath(string $option): string|null
    {
        $value = $this->extractCliOption($option);

        if ($value === null) {
            return null;
        }

        return $this->fileHelper->normalizePath($this->fileHelper->absolutizePath($value));
    }

    private function extractCliOption(string $name): string|null
    {
        $argv = $_SERVER['argv'] ?? null;

        if (! is_array($argv)) {
            return null;
        }

        $flag   = '--' . $name;
        $prefix = $flag . '=';
        $count  = count($argv);

        for ($i = 0; $i < $count; $i++) {
            $arg = $argv[$i];

            if (! is_string($arg)) {
                continue;
            }

            if ($arg === $flag && isset($argv[$i + 1]) && is_string($argv[$i + 1])) {
                return $argv[$i + 1];
            }

            if (str_starts_with($arg, $prefix)) {
                return substr($arg, strlen($prefix));
            }
        }

        return null;
    }
}

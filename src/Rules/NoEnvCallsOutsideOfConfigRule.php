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
use function is_dir;
use function str_starts_with;

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

    /** @param  list<non-empty-string> $configDirectories */
    public function __construct(array $configDirectories, private FileHelper $fileHelper)
    {
        if (count($configDirectories) !== 0) {
            foreach ($configDirectories as $directory) {
                $this->configDirectories[] = $this->fileHelper->normalizePath($directory);
            }

            return;
        }

        $this->configDirectories = [config_path()]; // @phpstan-ignore-line
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

        if (! $this->isCalledOutsideOfConfig($node, $scope)) {
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

    protected function isCalledOutsideOfConfig(FuncCall $call, Scope $scope): bool
    {
        foreach ($this->configDirectories as $configDirectoryGlob) {
            foreach ((glob($configDirectoryGlob) ?: []) as $configDirectory) {
                $absolutePath = $this->fileHelper->absolutizePath($configDirectory);

                if (! is_dir($absolutePath)) {
                    continue;
                }

                if (str_starts_with($scope->getFile(), $absolutePath)) {
                    return false;
                }
            }
        }

        return true;
    }
}

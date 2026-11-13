<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Application;
use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\FileNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;

/**
 * Larastan boots the application and resolves services from its container, so the types
 * of an analysed file can depend on the environment file the application loaded.
 * This rule never reports errors. It only tells the result cache to analyse a file
 * again when that environment file changes, is created or is deleted.
 *
 * @implements Rule<FileNode>
 */
#[AutowiredService]
final class EnvironmentFileDependencyRule implements Rule
{
    use HasContainer;

    public function getNodeType(): string
    {
        return FileNode::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     *
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $application = $this->getContainer();

        if (! $application instanceof Application) {
            return [];
        }

        $scope->trackFileDependency($application->environmentFilePath());

        return [];
    }
}

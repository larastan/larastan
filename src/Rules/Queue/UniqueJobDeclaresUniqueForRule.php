<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * Every job implementing `ShouldBeUnique` must declare `uniqueFor`, either as a
 * property (`public int $uniqueFor`) or a method (`public function uniqueFor(): int`).
 *
 * Without `uniqueFor`, Laravel holds the uniqueness lock until the job finishes
 * processing. If the worker dies mid job (OOM, deploy, fatal) the lock is never
 * released and the job can never be dispatched again until the cache key is
 * cleared by hand. Declaring `uniqueFor` bounds the lock so a stuck job self
 * heals after the timeout.
 *
 * @implements Rule<InClassNode>
 */
class UniqueJobDeclaresUniqueForRule implements Rule
{
    use InspectsQueuedJobs;

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (! $this->isDispatchableClass($classReflection)) {
            return [];
        }

        if (! $classReflection->is(ShouldBeUnique::class)) {
            return [];
        }

        if ($classReflection->hasNativeProperty('uniqueFor') || $classReflection->hasNativeMethod('uniqueFor')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Job '%s' implements ShouldBeUnique but does not declare uniqueFor, so a worker that dies mid job leaks the lock and the job can never be dispatched again. Add a 'public int \$uniqueFor' property or a 'uniqueFor()' method.",
                $classReflection->getDisplayName(),
            ))
                ->identifier('larastan.uniqueJobUniqueFor')
                ->line($node->getStartLine())
                ->build(),
        ];
    }
}

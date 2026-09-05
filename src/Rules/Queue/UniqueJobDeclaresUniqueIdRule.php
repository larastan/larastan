<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * A *parameterized* job implementing `ShouldBeUnique` must declare `uniqueId`,
 * either as a method (`public function uniqueId(): string`) or a property
 * (`public $uniqueId`).
 *
 * Laravel builds the uniqueness lock key as `laravel_unique_job:<class>:<uniqueId>`
 * and falls back to an empty `uniqueId` when neither is declared (see
 * `Illuminate\Bus\UniqueLock::getKey()`). For a job that carries no distinguishing
 * state that empty key is correct: the class is a singleton, only one may run at
 * a time. But for a job whose constructor takes arguments (per company, per
 * product, ...) the empty key collapses *every* dispatch into one unique job
 * regardless of those arguments, so legitimately distinct jobs are silently
 * dropped at dispatch with no error.
 *
 * The rule therefore fires only when the job has a constructor with at least one
 * parameter. A genuinely class wide unique job satisfies it by declaring
 * `uniqueId()` returning a constant, which makes that intent explicit.
 *
 * @implements Rule<InClassNode>
 */
class UniqueJobDeclaresUniqueIdRule implements Rule
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

        if (! $this->hasConstructorParameters($classReflection)) {
            // Parameterless job: the class name only lock key is correct.
            return [];
        }

        if ($classReflection->hasNativeMethod('uniqueId') || $classReflection->hasNativeProperty('uniqueId')) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Job '%s' implements ShouldBeUnique and is parameterized but does not declare uniqueId, so every dispatch shares one lock key whatever the constructor arguments and distinct jobs are silently dropped. Add a 'uniqueId()' method derived from the distinguishing arguments, or return a constant from it for an intentionally class wide job.",
                $classReflection->getDisplayName(),
            ))
                ->identifier('larastan.uniqueJobUniqueId')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function hasConstructorParameters(ClassReflection $classReflection): bool
    {
        if (! $classReflection->hasConstructor()) {
            return false;
        }

        $variants = $classReflection->getConstructor()->getVariants();

        return $variants !== [] && $variants[0]->getParameters() !== [];
    }
}

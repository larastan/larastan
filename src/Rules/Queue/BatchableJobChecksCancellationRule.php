<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Name;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function sprintf;

/**
 * A queued job that uses the `Batchable` trait must respect early batch
 * cancellation, either by checking `$this->batch()?->cancelled()` at the start of
 * `handle()`, or by registering the `SkipIfBatchCancelled` middleware from
 * `middleware()`.
 *
 * Cancelling a batch (`$batch->cancel()`, or the automatic cancel on first
 * failure when the batch is not `allowFailures`) only stops *future* dispatches
 * from running their body. Laravel does not forcibly kill jobs already on the
 * queue: each still wakes up and, unless it checks `cancelled()`, runs its full
 * body. That is wasted work at best, and at worst it keeps mutating state
 * (writing files, calling external APIs, charging cards) for a batch the caller
 * has already abandoned.
 *
 * To report the requirement once per hierarchy at its source, the rule fires on
 * the first concrete class in the chain that carries `Batchable`: a concrete
 * subclass whose parent already has the trait is skipped, because the guard
 * belongs on, or is inherited from, that ancestor. The guard is detected by
 * inspecting the class under analysis, so centralising the skip middleware on a
 * concrete base class satisfies the whole hierarchy.
 *
 * @implements Rule<InClassNode>
 */
class BatchableJobChecksCancellationRule implements Rule
{
    use InspectsQueuedJobs;

    private const SKIP_MIDDLEWARE_SHORT_NAME = 'SkipIfBatchCancelled';

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

        if (! $classReflection->is(ShouldQueue::class)) {
            return [];
        }

        if (! $this->usesTrait($classReflection, Batchable::class)) {
            return [];
        }

        if ($this->concreteAncestorUsesBatchable($classReflection)) {
            // A concrete ancestor already carries Batchable and owns the guard,
            // so it is reported there and not again on every subclass.
            return [];
        }

        if ($this->guardsCancellation($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Job '%s' uses the Batchable trait but never checks whether its batch has been cancelled, so it still runs its full body for an abandoned batch. Guard the work with 'if (\$this->batch()?->cancelled()) { return; }' at the start of handle(), or register the 'SkipIfBatchCancelled' middleware.",
                $classReflection->getDisplayName(),
            ))
                ->identifier('larastan.batchableJobChecksCancellation')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function concreteAncestorUsesBatchable(ClassReflection $classReflection): bool
    {
        foreach ($classReflection->getParents() as $parent) {
            if (! $parent->isAbstract() && $this->usesTrait($parent, Batchable::class)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The class satisfies the requirement when its own body either calls
     * `cancelled()` (the `$this->batch()?->cancelled()` guard in `handle()`) or
     * references the `SkipIfBatchCancelled` middleware. Inspecting the AST of the
     * class under analysis keeps the check local and deterministic.
     */
    private function guardsCancellation(InClassNode $node): bool
    {
        $finder     = new NodeFinder();
        $statements = $node->getOriginalNode()->stmts;

        $cancelledCall = $finder->findFirst(
            $statements,
            static fn (Node $node): bool => ($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
                && $node->name instanceof Node\Identifier
                && $node->name->toString() === 'cancelled',
        );

        if ($cancelledCall !== null) {
            return true;
        }

        // Matched on the short name too, so the root namespace alias is
        // recognised alongside the imported class.
        return $finder->findFirst(
            $statements,
            static fn (Node $node): bool => $node instanceof Name
                && ($node->toString() === SkipIfBatchCancelled::class || $node->getLast() === self::SKIP_MIDDLEWARE_SHORT_NAME),
        ) !== null;
    }
}

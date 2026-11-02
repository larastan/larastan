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
use PHPStan\Parser\Parser;
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
 * Each concrete job is checked using its effective methods, including methods
 * inherited from parents and traits. Overridden methods do not supply a guard.
 *
 * @implements Rule<InClassNode>
 */
class BatchableJobChecksCancellationRule implements Rule
{
    use InspectsQueuedJobs;

    private const SKIP_MIDDLEWARE_SHORT_NAME = 'SkipIfBatchCancelled';

    public function __construct(private Parser $parser)
    {
    }

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

        if ($this->guardsCancellation($classReflection)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Batchable job %s does not check for batch cancellation.',
                $classReflection->getDisplayName(),
            ))
                ->tip('Check $this->batch()?->cancelled() or use the SkipIfBatchCancelled middleware.')
                ->identifier('larastan.batchableJobChecksCancellation')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function guardsCancellation(ClassReflection $classReflection): bool
    {
        $finder        = new NodeFinder();
        $files         = [];
        $statements    = [];
        $batchableFile = $classReflection->getTraits(true)[Batchable::class]->getFileName();

        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            $fileName = $method->getFileName();

            // Batchable::batching() checks cancellation itself, but merely using
            // the trait does not mean the job calls that helper.
            if ($fileName === false || $fileName === $batchableFile) {
                continue;
            }

            $files[$fileName] ??= $this->parser->parseFile($fileName);
            $methodNode         = $finder->findFirst(
                $files[$fileName],
                static fn (Node $node): bool => $node instanceof Node\Stmt\ClassMethod
                    && $node->getStartLine() === $method->getStartLine()
                    && $node->getEndLine() === $method->getEndLine(),
            );

            if ($methodNode === null) {
                continue;
            }

            $statements[] = $methodNode;
        }

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

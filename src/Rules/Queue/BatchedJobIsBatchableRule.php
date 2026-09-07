<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;

use function implode;
use function sprintf;

/**
 * Every job dispatched through `Bus::batch([...])` must use the `Batchable` trait.
 *
 * The batch wires each job back to its parent batch so the job can read progress
 * and short circuit (`$this->batch()->cancelled()`), and so the batch can
 * reconcile its job count and fire its then/catch/finally callbacks. All of that
 * lives in `Batchable`. A job added to a batch without it has no `batch()`
 * method, so `$this->batch()` is a fatal call to an undefined method the moment
 * the job touches it, and the batch cannot account for the job either. The
 * framework does not validate this at dispatch, so the breakage only surfaces in
 * the worker.
 *
 * The rule inspects the array literal passed to `Bus::batch()` and flags every
 * element that is a queued job but does not use `Batchable`. It recurses into
 * nested arrays, which represent chains within a batch, because chained jobs need
 * the trait too.
 *
 * @implements Rule<StaticCall>
 */
class BatchedJobIsBatchableRule implements Rule
{
    use InspectsQueuedJobs;

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param StaticCall $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->isBusBatchCall($node)) {
            return [];
        }

        $jobsArg = $node->getArgs()[0] ?? null;

        if ($jobsArg === null || ! $jobsArg->value instanceof Array_) {
            // Only array literals can be inspected element by element. A variable
            // or a collection is left alone.
            return [];
        }

        return $this->checkJobExpressions($jobsArg->value, $scope);
    }

    private function isBusBatchCall(StaticCall $node): bool
    {
        if (! $node->class instanceof Name) {
            return false;
        }

        if (! $node->name instanceof Node\Identifier || $node->name->toString() !== 'batch') {
            return false;
        }

        return $this->isFacade($node->class, Bus::class, 'Bus');
    }

    /**
     * Walk an array of jobs, recursing into nested arrays (which represent chains
     * within a batch), and flag every queued job that lacks `Batchable`.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkJobExpressions(Array_ $array, Scope $scope): array
    {
        $errors = [];

        foreach ($array->items as $item) {
            if ($item->value instanceof Array_) {
                foreach ($this->checkJobExpressions($item->value, $scope) as $error) {
                    $errors[] = $error;
                }

                continue;
            }

            $type = $scope->getType($item->value);

            if (! $this->isNonBatchableQueuedJob($type)) {
                continue;
            }

            $classNames = $type->getObjectClassNames();

            $errors[] = RuleErrorBuilder::message(sprintf(
                "Job '%s' is dispatched in 'Bus::batch()' but does not use the Batchable trait, so it has no '\$this->batch()' accessor and the batch cannot track it. Add 'use Illuminate\Bus\Batchable;' to the job.",
                $classNames === [] ? 'dispatched here' : implode('|', $classNames),
            ))
                ->identifier('larastan.batchedJobIsBatchable')
                ->line($item->value->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isNonBatchableQueuedJob(Type $type): bool
    {
        $reflections = $type->getObjectClassReflections();

        if ($reflections === []) {
            return false;
        }

        foreach ($reflections as $classReflection) {
            // Only constrain queued jobs. A non job object in the array is not
            // this rule's concern.
            if (! $classReflection->is(ShouldQueue::class)) {
                return false;
            }

            if ($this->usesTrait($classReflection, Batchable::class)) {
                return false;
            }
        }

        return true;
    }
}

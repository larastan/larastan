<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function implode;
use function in_array;
use function sprintf;

/**
 * A job implementing `ShouldBeUnique` must not be dispatched through the bulk or
 * batch entry points: `Bus::batch([...])`, `Bus::bulk([...])` or the equivalent
 * `Queue::bulk([...])`.
 *
 * Both bypass the per job uniqueness guarantee:
 *
 *   - `Queue::bulk()` and `Bus::bulk()` push raw payloads straight onto the
 *     queue, skipping the dispatcher path that acquires the unique lock, so
 *     duplicates are queued and `ShouldBeUnique` silently does nothing.
 *   - Batching a unique job means a duplicate is dropped at dispatch, but the
 *     batch's job count is computed up front, so the batch's progress and
 *     then/finally callbacks never reconcile and the batch can hang as pending.
 *
 * Dispatch unique jobs individually instead.
 *
 * @implements Rule<StaticCall>
 */
class NoBatchedUniqueJobRule implements Rule
{
    use InspectsQueuedJobs;

    private const BULK_METHODS = ['batch', 'bulk'];

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
        if (! $this->isBulkOrBatchCall($node)) {
            return [];
        }

        $jobsArg = $node->getArgs()[0] ?? null;

        if ($jobsArg === null) {
            return [];
        }

        $method = $node->name instanceof Node\Identifier ? $node->name->toString() : 'batch';

        // Array literal: inspect each element so the error points at the exact
        // offending job and names it.
        if ($jobsArg->value instanceof Array_) {
            return $this->checkJobExpressions($jobsArg->value, $scope, $method);
        }

        // Anything else (a variable, a collection): fall back to the iterable
        // value type and report once at the call site.
        return $this->checkIterableType($scope->getType($jobsArg->value), $node, $method);
    }

    private function isBulkOrBatchCall(StaticCall $node): bool
    {
        if (! $node->class instanceof Name) {
            return false;
        }

        if (! $node->name instanceof Node\Identifier || ! in_array($node->name->toString(), self::BULK_METHODS, true)) {
            return false;
        }

        return $this->isFacade($node->class, Bus::class, 'Bus')
            || $this->isFacade($node->class, Queue::class, 'Queue');
    }

    /**
     * Walk an array of jobs, recursing into nested arrays (which represent chains
     * within a batch), and flag every ShouldBeUnique element.
     *
     * @return list<IdentifierRuleError>
     */
    private function checkJobExpressions(Array_ $array, Scope $scope, string $method): array
    {
        $errors = [];

        foreach ($array->items as $item) {
            if ($item->value instanceof Array_) {
                foreach ($this->checkJobExpressions($item->value, $scope, $method) as $error) {
                    $errors[] = $error;
                }

                continue;
            }

            $type = $scope->getType($item->value);

            if (! $this->isUniqueJobType($type)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message($this->buildMessage($type, $method))
                ->identifier('larastan.noBatchedUniqueJob')
                ->line($item->value->getStartLine())
                ->build();
        }

        return $errors;
    }

    /** @return list<IdentifierRuleError> */
    private function checkIterableType(Type $jobsType, StaticCall $node, string $method): array
    {
        if (! $jobsType->isIterable()->yes()) {
            return [];
        }

        $valueType = $jobsType->getIterableValueType();

        if (! $this->isUniqueJobType($valueType)) {
            return [];
        }

        return [
            RuleErrorBuilder::message($this->buildMessage($valueType, $method))
                ->identifier('larastan.noBatchedUniqueJob')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    private function isUniqueJobType(Type $type): bool
    {
        foreach ($type->getObjectClassReflections() as $classReflection) {
            if ($this->isDispatchableClass($classReflection) && $classReflection->is(ShouldBeUnique::class)) {
                return true;
            }
        }

        // Fallback for types that carry the interface without a resolvable class
        // reflection, such as an interface typed variable.
        return (new ObjectType(ShouldBeUnique::class))->isSuperTypeOf($type)->yes();
    }

    private function buildMessage(Type $type, string $method): string
    {
        $classNames = $type->getObjectClassNames();

        return sprintf(
            "Job '%s' implements ShouldBeUnique and must not be dispatched via '%s()'. Bulk and batch dispatch bypass the uniqueness lock, dispatch the job individually instead.",
            $classNames === [] ? 'dispatched here' : implode('|', $classNames),
            $method,
        );
    }
}

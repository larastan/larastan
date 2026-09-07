<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_values;
use function in_array;
use function is_array;
use function spl_object_id;
use function sprintf;
use function strtolower;
use function version_compare;

/**
 * A queued job dispatched inside a `DB::transaction(...)` closure must defer its
 * dispatch until the transaction commits, by chaining `->afterCommit()` on the
 * dispatch, declaring `$afterCommit = true`, or implementing ShouldQueueAfterCommit.
 *
 * A queued job pushed during an open transaction can be picked up by a worker
 * before the transaction commits (a fast worker racing the still open connection):
 * the job then loads rows that are not visible yet and fails, or silently operates
 * on half written state. Worse, if the transaction rolls back the job still runs
 * against data that never existed. `afterCommit` holds the dispatch until the
 * outermost transaction commits, and drops it entirely on rollback.
 *
 * Scope and limits:
 *
 *   - Only the `DB::transaction(Closure)` and arrow function form is inspected.
 *     The manual `DB::beginTransaction()` ... `DB::commit()` form has no closure
 *     to bound the analysis and is not covered.
 *   - Only the chainable dispatch forms are flagged: `Job::dispatch(...)` and the
 *     `dispatch(new Job)` helper. Synchronous dispatch (`dispatchSync`,
 *     `dispatch_sync`) runs inline within the transaction by design, and the
 *     `Bus` and `Queue` facade entry points are a different mechanism, so both
 *     are left alone.
 *   - Non queued dispatchables are ignored: they run synchronously, so the commit
 *     race does not apply.
 *   - `->afterCommit()` only counts as protection when it is syntactically
 *     chained on the dispatch. Splitting it across statements
 *     (`$p = Job::dispatch(); $p->afterCommit();`) is not recognised.
 *   - The walk descends into nested closures, so a dispatch inside a callback
 *     that is merely *registered* within the transaction rather than run
 *     synchronously (`DB::afterCommit(fn () => Job::dispatch())`) is reported even
 *     though it does not race the commit. Chain `->afterCommit()` or move the
 *     dispatch to silence it.
 *   - The rule assumes the default queue config: a project that enables
 *     `after_commit` globally does not need it.
 *
 * @implements Rule<StaticCall>
 */
class JobDispatchedInTransactionUsesAfterCommitRule implements Rule
{
    use InspectsQueuedJobs;

    private const DISPATCH_METHOD = 'dispatch';

    private const AFTER_COMMIT_METHOD = 'afterCommit';

    private const AFTER_COMMIT_PROPERTY = 'afterCommit';

    private const BEFORE_COMMIT_METHOD = 'beforeCommit';

    /** Static call class names that do not name a concrete, resolvable job class. */
    private const NON_RESOLVABLE_CLASS_NAMES = ['self', 'static', 'parent'];

    private string $laravelVersion;

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
        $this->laravelVersion = LARAVEL_VERSION;
    }

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
        if (! $this->isTransactionCall($node)) {
            return [];
        }

        $callback = $node->getArgs()[0] ?? null;

        if ($callback === null) {
            return [];
        }

        $bodyNodes = $this->closureBody($callback->value);

        if ($bodyNodes === []) {
            return [];
        }

        $dispatches = [];
        $protected  = [];

        foreach ($bodyNodes as $bodyNode) {
            $this->visit($bodyNode, $dispatches, $protected);
        }

        $errors = [];

        foreach ($dispatches as $dispatch) {
            $afterCommit = $protected[spl_object_id($dispatch)] ?? null;

            if ($afterCommit === true) {
                continue;
            }

            $job = $this->dispatchedJobNeedingAfterCommit($dispatch, $scope, $afterCommit);

            if ($job === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Job %s is dispatched inside a transaction without deferring until commit.',
                $job->getDisplayName(),
            ))
                ->tip('Call afterCommit() on the dispatch or implement ShouldQueueAfterCommit.')
                ->identifier('larastan.dispatchInTransactionAfterCommit')
                ->line($dispatch->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isTransactionCall(StaticCall $node): bool
    {
        if (! $node->class instanceof Name) {
            return false;
        }

        if (! $node->name instanceof Node\Identifier || $node->name->toString() !== 'transaction') {
            return false;
        }

        return $this->isFacade($node->class, DB::class, 'DB');
    }

    /**
     * The statements or expression that make up the transaction callback's body,
     * or an empty list when the callback is not an inspectable closure literal.
     *
     * @return list<Node>
     */
    private function closureBody(Expr $callback): array
    {
        if ($callback instanceof Closure) {
            return array_values($callback->stmts);
        }

        if ($callback instanceof ArrowFunction) {
            return [$callback->expr];
        }

        return [];
    }

    /**
     * Walk the callback body, recording every dispatch call and the final
     * afterCommit/beforeCommit setting in its method chain. Nested
     * `DB::transaction()` calls are pruned: their own dispatches are reported when
     * that inner call is analysed, so descending here would report them twice.
     *
     * @param list<Node>      $dispatches
     * @param array<int,bool> $protected
     */
    private function visit(Node $node, array &$dispatches, array &$protected): void
    {
        if ($node instanceof StaticCall && $this->isTransactionCall($node)) {
            return;
        }

        if (
            ($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
            && $node->name instanceof Node\Identifier
            && in_array($node->name->toString(), [self::AFTER_COMMIT_METHOD, self::BEFORE_COMMIT_METHOD], true)
        ) {
            $guarded = $this->dispatchInReceiverChain($node);

            if ($guarded !== null) {
                // The outermost call executes last and is visited first.
                $protected[spl_object_id($guarded)] ??= $node->name->toString() === self::AFTER_COMMIT_METHOD;
            }
        }

        if ($this->isDispatchCall($node)) {
            $dispatches[] = $node;
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $sub      = $node->{$subNodeName};
            $children = is_array($sub) ? $sub : [$sub];

            foreach ($children as $child) {
                if (! $child instanceof Node) {
                    continue;
                }

                $this->visit($child, $dispatches, $protected);
            }
        }
    }

    private function isDispatchCall(Node $node): bool
    {
        if ($node instanceof FuncCall) {
            return $node->name instanceof Name && $node->name->getLast() === self::DISPATCH_METHOD;
        }

        if ($node instanceof StaticCall) {
            if (! $node->name instanceof Node\Identifier || $node->name->toString() !== self::DISPATCH_METHOD) {
                return false;
            }

            if (! $node->class instanceof Name) {
                return false;
            }

            return ! $this->isFacade($node->class, Bus::class, 'Bus')
                && ! $this->isFacade($node->class, Queue::class, 'Queue');
        }

        return false;
    }

    /**
     * Descend a method chain ending in `->afterCommit()` or `->beforeCommit()`
     * and return the dispatch call it is applied to, for example the `Job::dispatch()` in
     * `Job::dispatch()->onQueue('x')->afterCommit()`.
     */
    private function dispatchInReceiverChain(Node $afterCommitCall): Node|null
    {
        $current = $afterCommitCall;

        while ($current instanceof MethodCall || $current instanceof NullsafeMethodCall) {
            $receiver = $current->var;

            if ($this->isDispatchCall($receiver)) {
                return $receiver;
            }

            $current = $receiver;
        }

        return null;
    }

    /**
     * Resolve the dispatched job when it needs an explicit afterCommit() call,
     * accounting for the final setting in the dispatch chain.
     */
    private function dispatchedJobNeedingAfterCommit(Node $dispatch, Scope $scope, bool|null $afterCommit): ClassReflection|null
    {
        foreach ($this->dispatchedJobReflections($dispatch, $scope) as $reflection) {
            if ($reflection->is(ShouldQueue::class) && ! $this->declaresAfterCommit($reflection, $afterCommit)) {
                return $reflection;
            }
        }

        return null;
    }

    /** @return list<ClassReflection> */
    private function dispatchedJobReflections(Node $dispatch, Scope $scope): array
    {
        if ($dispatch instanceof StaticCall && $dispatch->class instanceof Name) {
            $className = $dispatch->class->toString();

            if (in_array(strtolower($className), self::NON_RESOLVABLE_CLASS_NAMES, true)) {
                return [];
            }

            if (! $this->reflectionProvider->hasClass($className)) {
                return [];
            }

            return [$this->reflectionProvider->getClass($className)];
        }

        if ($dispatch instanceof FuncCall) {
            $jobArg = $dispatch->getArgs()[0] ?? null;

            if ($jobArg === null) {
                return [];
            }

            return $scope->getType($jobArg->value)->getObjectClassReflections();
        }

        return [];
    }

    /**
     * Combine the dispatch chain setting, property default and after-commit
     * contract using the installed Laravel version's precedence.
     */
    private function declaresAfterCommit(ClassReflection $classReflection, bool|null $afterCommit): bool
    {
        $native = $classReflection->getNativeReflection();

        $afterCommit ??= $native->hasProperty(self::AFTER_COMMIT_PROPERTY)
            ? $native->getProperty(self::AFTER_COMMIT_PROPERTY)->getDefaultValue()
            : null;

        if ($classReflection->is(ShouldQueueAfterCommit::class)) {
            // Laravel 12.22 added beforeCommit() support for this contract.
            return version_compare($this->laravelVersion, '12.22.0', '<') || $afterCommit !== false;
        }

        return $afterCommit === true;
    }
}

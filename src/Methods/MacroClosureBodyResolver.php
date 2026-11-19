<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Closure;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ClosureType;
use ReflectionFunction;
use Throwable;

use function array_filter;
use function array_key_exists;
use function array_values;
use function count;
use function is_file;

/**
 * PROTOTYPE: infers a macro closure's type from its body.
 *
 * Locates the closure's AST node through native reflection, then asks a scope
 * bound to the macroable class for the closure's type.
 */
#[AutowiredService]
final class MacroClosureBodyResolver
{
    /** @var array<string, ClosureType|null> */
    private array $cache = [];

    /** @var array<string, true> */
    private array $inProgress = [];

    public function __construct(
        private ScopeFactory $scopeFactory,
        #[AutowiredParameter(ref: '@cachedRichParser')]
        private Parser $parser,
    ) {
    }

    public function resolve(Closure $closure, ClassReflection $classReflection, string $methodName): ClosureType|null
    {
        $key = $classReflection->getCacheKey() . '::' . $methodName;

        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        // A body that calls this macro (or a macro cycle) re-enters here.
        if (array_key_exists($key, $this->inProgress)) {
            return null;
        }

        $this->inProgress[$key] = true;

        try {
            $type = $this->doResolve($closure, $classReflection);
        } catch (Throwable) {
            $type = null;
        } finally {
            unset($this->inProgress[$key]);
        }

        return $this->cache[$key] = $type;
    }

    private function doResolve(Closure $closure, ClassReflection $classReflection): ClosureType|null
    {
        $reflection = new ReflectionFunction($closure);
        $file       = $reflection->getFileName();
        $startLine  = $reflection->getStartLine();
        $endLine    = $reflection->getEndLine();

        if ($file === false || $startLine === false || $endLine === false || ! is_file($file)) {
            return null;
        }

        $candidates = (new NodeFinder())->find(
            $this->parser->parseFile($file),
            static fn (Node $node): bool => ($node instanceof Node\Expr\Closure || $node instanceof Node\Expr\ArrowFunction)
                && $node->getStartLine() === $startLine
                && $node->getEndLine() === $endLine,
        );

        // A one-line closure shares its lines with the closures nested in it; keep the outermost.
        $candidates = array_values(array_filter($candidates, static function (Node $node) use ($candidates): bool {
            foreach ($candidates as $other) {
                if ($other !== $node && $other->getStartFilePos() <= $node->getStartFilePos() && $other->getEndFilePos() >= $node->getEndFilePos()) {
                    return false;
                }
            }

            return true;
        }));

        // Zero: first-class callable or generated code. Several: ambiguous.
        if (count($candidates) !== 1 || ! $candidates[0] instanceof Node\Expr) {
            return null;
        }

        $scope = $this->scopeFactory->create(ScopeContext::create($file))->enterClass($classReflection);
        $type  = $scope->getType($candidates[0]);

        return $type instanceof ClosureType ? $type : null;
    }
}

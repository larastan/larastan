<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\View\ViewName;
use Larastan\Larastan\Support\CallArgumentsHelper;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;

use function count;

/** @implements Collector<Node\Expr\FuncCall, string> */
#[AutowiredService]
final class UsedViewFunctionCollector implements Collector
{
    public function __construct(private ReflectionProvider $reflectionProvider, private CallArgumentsHelper $callArgumentsHelper)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    /** @param Node\Expr\FuncCall $node */
    public function processNode(Node $node, Scope $scope): string|null
    {
        $funcName = $node->name;

        if (! $funcName instanceof Node\Name) {
            return null;
        }

        if ($this->reflectionProvider->resolveFunctionName($funcName, $scope) !== 'view') {
            return null;
        }

        $args = $this->callArgumentsHelper->ofFunctionCall($node, $scope);

        if (! isset($args[0])) {
            return null;
        }

        $template = $args[0]->value;

        if ($template instanceof Node\Scalar\String_) {
            return ViewName::normalize($template->value);
        }

        if ($template instanceof Node\Expr\BinaryOp\Concat) {
            $template = $scope->getType($template);

            $constantStrings = $template->getConstantStrings();

            if (count($constantStrings) !== 1) {
                return null;
            }

            return ViewName::normalize($constantStrings[0]->getValue());
        }

        return null;
    }
}

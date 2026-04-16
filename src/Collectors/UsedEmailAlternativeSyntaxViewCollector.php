<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Mail\Mailables\Content;
use Illuminate\View\ViewName;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;

use function count;
use function in_array;

/** @implements Collector<Node\Expr\New_, list<string>> */
final class UsedEmailAlternativeSyntaxViewCollector implements Collector
{
    private const VIEW_PARAM_NAMES = ['view', 'html', 'text', 'markdown'];

    public function getNodeType(): string
    {
        return Node\Expr\New_::class;
    }

    /** @param Node\Expr\New_ $node */
    public function processNode(Node $node, Scope $scope): array|null
    {
        $class = $node->class;

        if (! $class instanceof Node\Name) {
            return null;
        }

        if (count($node->getArgs()) < 1) {
            return null;
        }

        $class = $scope->resolveName($class);

        if ($class !== Content::class) {
            return null;
        }

        $views = [];

        foreach ($node->getArgs() as $index => $arg) {
            if ($arg->name !== null) {
                $paramName = $arg->name->name;
            } else {
                $paramName = self::VIEW_PARAM_NAMES[$index] ?? null;
            }

            if ($paramName === null || ! in_array($paramName, self::VIEW_PARAM_NAMES, true)) {
                continue;
            }

            if (! $arg->value instanceof Node\Scalar\String_) {
                continue;
            }

            $views[] = ViewName::normalize($arg->value->value);
        }

        return $views !== [] ? $views : null;
    }
}

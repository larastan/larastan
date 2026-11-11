<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Mail\Mailables\Content;
use Illuminate\View\ViewName;
use Larastan\Larastan\Support\CallArgumentsHelper;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;

use function array_slice;

/** @implements Collector<Node\Expr\New_, list<string>> */
#[AutowiredService]
final class UsedEmailAlternativeSyntaxViewCollector implements Collector
{
    public function __construct(private CallArgumentsHelper $callArgumentsHelper)
    {
    }

    /** The constructor takes the view, html, text and markdown views first. */
    private const VIEW_PARAMETERS = 4;

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

        $class = $scope->resolveName($class);

        if ($class !== Content::class) {
            return null;
        }

        $views = [];

        foreach (array_slice($this->callArgumentsHelper->ofNew($node, $scope) ?? [], 0, self::VIEW_PARAMETERS) as $arg) {
            if (! $arg->value instanceof Node\Scalar\String_) {
                continue;
            }

            $views[] = ViewName::normalize($arg->value->value);
        }

        return $views !== [] ? $views : null;
    }
}

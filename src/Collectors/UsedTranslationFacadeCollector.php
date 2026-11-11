<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Support\Facades\Lang;
use Larastan\Larastan\Support\CallArgumentsHelper;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ObjectType;

use function in_array;

/** @implements Collector<Node\Expr\StaticCall, string> */
#[AutowiredService]
final class UsedTranslationFacadeCollector implements Collector
{
    public function __construct(private CallArgumentsHelper $callArgumentsHelper)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    /**
     * @param Node\Expr\StaticCall $node
     *
     * @return array{0: string, 1: int}
     */
    public function processNode(Node $node, Scope $scope): array|null
    {
        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return null;
        }

        if (! in_array($name->name, ['get', 'choice'], true)) {
            return null;
        }

        $class = $node->class;

        if (! $class instanceof Node\Name) {
            return null;
        }

        $class = $scope->resolveName($class);

        if (! (new ObjectType(Lang::class))->isSuperTypeOf(new ObjectType($class))->yes()) {
            return null;
        }

        $args = $this->callArgumentsHelper->ofMethodCall($node, $scope);

        if (! isset($args[0])) {
            return null;
        }

        $template = $args[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return [$template->value, $node->getStartLine()];
    }
}

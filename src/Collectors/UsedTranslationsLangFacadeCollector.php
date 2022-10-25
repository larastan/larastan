<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Collectors;

use Illuminate\Support\Facades\View;
use Illuminate\View\ViewName;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

/** @implements Collector<Node\Expr\StaticCall, string> */
final class UsedTranslationsLangFacadeCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    /** @param Node\Expr\StaticCall $node */
    public function processNode(Node $node, Scope $scope): ?string
    {
        $name = $node->name;

        if (! $name instanceof Node\Identifier) {
            return null;
        }

        if (! in_array($name->name, ['get', 'choice', 'has', 'hasForLocale'], true)) {
            return null;
        }

        if (count($node->getArgs()) < 1) {
            return null;
        }

        $class = $node->class;

        if (! $class instanceof Node\Name) {
            return null;
        }

        if (! (new ObjectType(View::class))->isSuperTypeOf($scope->resolveTypeByName($class))->yes()) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}

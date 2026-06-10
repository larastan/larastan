<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use function array_map;
use function array_shift;
use function count;
use function ctype_digit;
use function preg_split;
use function str_replace;

/** @internal */
final class RuleTreeBuilder
{
    /**
     * @param array<array-key, ValidationRule> $flatRules keyed by the raw rules() key
     *
     * @return array<string, RuleTreeNode> keyed by top-level property name
     */
    public static function build(array $flatRules): array
    {
        $roots = [];

        foreach ($flatRules as $key => $rule) {
            $segments = preg_split('/(?<!\\\\)\./', (string) $key);

            if ($segments === false) {
                continue;
            }

            $segments = array_map(
                static fn (string $segment): string => str_replace('\\.', '.', $segment),
                $segments,
            );

            $name = array_shift($segments);
            $root = $roots[$name] ??= new RuleTreeNode();

            if (self::containsUnsupportedSegment($segments)) {
                $root->degraded = true;

                continue;
            }

            $node = $root;

            foreach ($segments as $segment) {
                $node->children[$segment] ??= new RuleTreeNode();

                if (isset($node->children[RuleTreeNode::WILDCARD]) && count($node->children) > 1) {
                    $node->degraded = true;
                }

                $node = $node->children[$segment];
            }

            $node->rule = $rule;
        }

        return $roots;
    }

    /** @param list<string> $segments */
    private static function containsUnsupportedSegment(array $segments): bool
    {
        foreach ($segments as $segment) {
            if ($segment === '' || ctype_digit($segment)) {
                return true;
            }
        }

        return false;
    }
}

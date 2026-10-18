<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Octane;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;

use function class_exists;
use function strcasecmp;

/**
 * Attribute singleton checks must run after binding collection, not per file:
 * another analysed service provider may register this class as scoped.
 *
 * @internal
 *
 * @implements Collector<InClassNode, array{string, int}>
 */
final class SingletonAttributeCollector implements Collector
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return array{string, int}|null */
    public function processNode(Node $node, Scope $scope): array|null
    {
        $class = $node->getClassReflection();

        if ($class->isInterface() || $class->isAbstract()) {
            return null;
        }

        // Unknown native lifetime attribute classes (Laravel 11) must not
        // be treated as active Container lifetime declarations.
        if (! class_exists('Illuminate\\Container\\Attributes\\Singleton')) {
            return null;
        }

        foreach ($class->getNativeReflection()->getAttributes() as $attribute) {
            if (strcasecmp($attribute->getName(), 'Illuminate\\Container\\Attributes\\Singleton') === 0) {
                return [$class->getName(), $node->getStartLine()];
            }
        }

        return null;
    }
}

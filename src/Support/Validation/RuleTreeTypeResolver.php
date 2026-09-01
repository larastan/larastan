<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

use function in_array;

/** @internal */
final class RuleTreeTypeResolver
{
    public function __construct(private TypeStringResolver $stringResolver)
    {
    }

    public function resolveTopLevel(RuleTreeNode $node): Type
    {
        if ($node->children === [] && ! $node->degraded) {
            $type = $this->leafType($node);

            if ($node->rule !== null && ($node->rule->nullable || $node->rule->possiblyUndefined)) {
                $type = TypeCombinator::addNull($type);
            }

            return $type;
        }

        $type = $this->resolveNode($node);

        if (! $this->isGuaranteedPresent($node)) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    private function resolveNode(RuleTreeNode $node): Type
    {
        if ($node->degraded) {
            return new ArrayType(new MixedType(), new MixedType());
        }

        if ($node->children === [] || $this->hasConflictingScalarRule($node)) {
            $type = $this->leafType($node);
        } elseif (isset($node->children[RuleTreeNode::WILDCARD])) {
            // The builder degrades levels mixing wildcard and named segments, so the wildcard is the only child here.
            $type = TypeCombinator::intersect(
                new ArrayType(new IntegerType(), $this->resolveNode($node->children[RuleTreeNode::WILDCARD])),
                new AccessoryArrayListType(),
            );
        } else {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($node->children as $segment => $child) {
                $builder->setOffsetValueType(
                    new ConstantStringType($segment),
                    $this->resolveNode($child),
                    $this->isOptionalKey($child),
                );
            }

            $type = $builder->getArray();
        }

        if ($node->rule?->nullable === true) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    /**
     * A scalar rule on a node that also has nested rules ('users' => 'string' +
     * 'users.*.x') means an array value can never pass validation — the scalar
     * type wins and the nested rules are discarded.
     */
    private function hasConflictingScalarRule(RuleTreeNode $node): bool
    {
        if ($node->rule === null) {
            return false;
        }

        return ! in_array($node->rule->type, ['array', 'list', 'mixed'], true);
    }

    private function isOptionalKey(RuleTreeNode $node): bool
    {
        if ($node->rule?->possiblyUndefined === true) {
            return true;
        }

        return ! $this->isGuaranteedPresent($node);
    }

    /**
     * A node is guaranteed to be present in the validated input when its own rules
     * contain a bare `required`, or when any descendant reachable without crossing
     * a wildcard is guaranteed present: `author.name => required` fails validation
     * when `author` is absent, while `users.*.email => required` passes when
     * `users` is absent (the wildcard expands to zero rules).
     */
    private function isGuaranteedPresent(RuleTreeNode $node): bool
    {
        if ($node->rule?->required === true) {
            return true;
        }

        foreach ($node->children as $segment => $child) {
            if ($segment === RuleTreeNode::WILDCARD) {
                continue;
            }

            if ($this->isGuaranteedPresent($child)) {
                return true;
            }
        }

        return false;
    }

    private function leafType(RuleTreeNode $node): Type
    {
        if ($node->rule === null) {
            return new MixedType();
        }

        $type = $this->stringResolver->resolve($node->rule->type);

        return $node->rule->benevolent ? TypeUtils::toBenevolentUnion($type) : $type;
    }
}

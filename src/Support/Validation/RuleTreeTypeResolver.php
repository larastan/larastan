<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;

/** @internal */
final class RuleTreeTypeResolver
{
    /**
     * @param array<string, RuleTreeNode> $nodes
     *
     * @return array<string, Type>
     */
    public function resolveRawProperties(array $nodes): array
    {
        return array_map($this->resolveRawTopLevel(...), $nodes);
    }

    /** @param array<string, RuleTreeNode> $nodes */
    public function resolveValidatedData(array $nodes, bool $unsealed): Type
    {
        $builder = $this->resolveNamedNodes($nodes, raw: false);

        if ($unsealed) {
            $builder->makeUnsealed(new MixedType(), new MixedType());
        }

        return $builder->getArray();
    }

    private function resolveRawTopLevel(RuleTreeNode $node): Type
    {
        $type = $this->resolveRawNode($node);

        if (! $this->isGuaranteedPresent($node, raw: true)) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    private function resolveRawNode(RuleTreeNode $node): Type
    {
        if ($node->rule?->flags->possiblyExcluded === true || $node->rule?->flags->degraded === true) {
            return new MixedType();
        }

        // A scalar parent cannot validate as an array, so conflicting nested rules cannot refine it.
        if ($node->rule?->isScalarOnly() === true) {
            return $this->resolveLeaf($node);
        }

        if ($node->rule?->allowedKeys !== null) {
            return $this->resolveAllowedKeys($node, raw: true);
        }

        if ($node->degraded) {
            return $node->rule?->isContainer() === true ? $this->resolveLeaf($node) : new MixedType();
        }

        if ($node->children === []) {
            return $this->resolveLeaf($node);
        }

        if (
            $node->rule?->isContainer() !== true
            && $node->rule?->mayBeContainer() !== true
            && ! $this->hasGuaranteedNamedDescendant($node, raw: true)
        ) {
            return $this->resolveLeaf($node);
        }

        $type = isset($node->children[RuleTreeNode::WILDCARD])
            ? $this->resolveWildcardNode($node, raw: true)
            : $this->resolveNamedNodes($node->children, raw: true)->getArray();

        if ($node->rule !== null && $node->rule->anyOfRuleGroups !== []) {
            $ruleType = $node->rule->resolveType();
            $type     = TypeCombinator::intersect($type, $ruleType);

            if (! $this->hasGuaranteedNamedDescendant($node, raw: true)) {
                $type = TypeCombinator::union(
                    $type,
                    TypeCombinator::remove($ruleType, new ArrayType(new MixedType(), new MixedType())),
                );
            }
        }

        return $this->addNullable($node, $type);
    }

    private function resolveValidatedNode(RuleTreeNode $node, bool $mayBeCopiedWhole = false): Type
    {
        if ($node->rule?->flags->degraded === true) {
            return new MixedType();
        }

        if ($node->rule?->isScalarOnly() === true) {
            return $this->resolveLeaf($node);
        }

        // A parameterized array rule copies the parent unless a separate bare
        // array/list rule prunes it. Excluding every child can also restore copying.
        $mayBeCopiedWhole = $mayBeCopiedWhole
            || ($node->rule?->isContainer() === true
                && ($node->rule->flags->prunesUnvalidatedKeys !== true || $this->canExcludeAllDescendantRules($node, true)));

        if ($node->rule?->allowedKeys !== null) {
            return $this->resolveAllowedKeys($node, raw: false, mayBeCopiedWhole: $mayBeCopiedWhole);
        }

        if ($this->isValidatedParentCopiedWhole($node) && $node->rule?->isContainer() !== true) {
            return $node->degraded ? $this->resolveLeaf($node) : $this->resolveRawNode($node);
        }

        if ($node->degraded) {
            $type = $node->rule?->isContainer() === true
                ? $node->rule->resolveType()
                : new ArrayType(new MixedType(), new MixedType());

            return $this->addNullable($node, $type);
        }

        if ($node->children === []) {
            return $this->resolveLeaf($node);
        }

        if ($mayBeCopiedWhole && $node->rule === null && ! $this->hasGuaranteedNamedDescendant($node, raw: true)) {
            return new MixedType();
        }

        $type = isset($node->children[RuleTreeNode::WILDCARD])
            ? $this->resolveWildcardNode($node, raw: false, mayBeCopiedWhole: $mayBeCopiedWhole)
            : $this->resolveNamedNodes($node->children, raw: false, mayBeCopiedWhole: $mayBeCopiedWhole)->getArray();

        return $this->addNullable($node, $type);
    }

    /** @param array<string, RuleTreeNode> $nodes */
    private function resolveNamedNodes(array $nodes, bool $raw, bool $mayBeCopiedWhole = false): ConstantArrayTypeBuilder
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($nodes as $segment => $child) {
            if (! $raw && $child->rule?->flags->excluded === true) {
                continue;
            }

            $builder->setOffsetValueType(
                new ConstantStringType($segment),
                $raw ? $this->resolveRawNode($child) : $this->resolveValidatedNode($child, $mayBeCopiedWhole),
                ! $this->isGuaranteedPresent($child, $raw),
            );
        }

        if ($raw || $mayBeCopiedWhole) {
            $builder->makeUnsealed(new MixedType(), new MixedType());
        }

        return $builder;
    }

    private function canExcludeAllDescendantRules(RuleTreeNode $node, bool $conditional): bool
    {
        if ($node->degraded) {
            return false;
        }

        foreach ($node->children as $child) {
            if ($child->rule?->flags->excluded === true || ($conditional && $child->rule?->flags->possiblyExcluded === true)) {
                continue;
            }

            // An absent optional value still leaves its rule in the validator,
            // preventing Laravel from copying the parent array as a whole.
            if ($child->rule !== null || ! $this->canExcludeAllDescendantRules($child, $conditional)) {
                return false;
            }
        }

        return true;
    }

    private function isValidatedParentCopiedWhole(RuleTreeNode $node): bool
    {
        return $node->rule !== null
            && (
                ! $node->rule->isContainer()
                || $node->rule->flags->prunesUnvalidatedKeys === false
            )
            && ($node->children !== [] || $node->degraded);
    }

    /**
     * Required named descendants force their ancestors to exist, unlike wildcards
     * which can expand to zero rules. In validated output, a required parent only
     * guarantees its own presence when it survives pruning or is copied whole.
     */
    private function isGuaranteedPresent(RuleTreeNode $node, bool $raw): bool
    {
        if ($node->rule?->flags->possiblyExcluded === true || $node->rule?->flags->degraded === true) {
            return false;
        }

        if (
            $node->rule?->flags->required === true
            && ! $node->rule->flags->possiblyUndefined
            && (
                $raw
                || $node->rule->isScalarOnly()
                || $this->isValidatedParentCopiedWhole($node)
                || ($node->rule->isContainer() && $this->canExcludeAllDescendantRules($node, false))
                || ($node->children === [] && ! $node->degraded)
            )
        ) {
            return true;
        }

        return $this->hasGuaranteedNamedDescendant($node, $raw);
    }

    private function hasGuaranteedNamedDescendant(RuleTreeNode $node, bool $raw): bool
    {
        foreach ($node->children as $segment => $child) {
            if ($segment !== RuleTreeNode::WILDCARD && $this->isGuaranteedPresent($child, $raw)) {
                return true;
            }
        }

        return false;
    }

    private function resolveLeaf(RuleTreeNode $node): Type
    {
        return $this->addNullable($node, $node->rule?->resolveType() ?? new MixedType());
    }

    private function addNullable(RuleTreeNode $node, Type $type): Type
    {
        if (
            $node->rule?->flags->nullable === true
            && ! $node->rule->flags->rejectsNull
            && ! $this->hasGuaranteedNamedDescendant($node, raw: true)
        ) {
            return TypeCombinator::addNull($type);
        }

        return $type;
    }

    private function resolveAllowedKeys(RuleTreeNode $node, bool $raw, bool $mayBeCopiedWhole = false): Type
    {
        $builder     = ConstantArrayTypeBuilder::createEmpty();
        $rawPresence = $raw || $this->isValidatedParentCopiedWhole($node);

        foreach ($node->rule->allowedKeys ?? [] as $keyType) {
            $child = $node->children[(string) $keyType->getValue()] ?? null;

            if (! $raw && $child?->rule?->flags->excluded === true) {
                continue;
            }

            if (! $raw && ! $mayBeCopiedWhole && $node->children !== [] && $child === null) {
                continue;
            }

            $type = new MixedType();

            if ($child !== null) {
                $type = $raw ? $this->resolveRawNode($child) : $this->resolveValidatedNode($child, $mayBeCopiedWhole);
            }

            $builder->setOffsetValueType(
                $keyType,
                $type,
                $child === null || ! $this->isGuaranteedPresent($child, $rawPresence),
            );
        }

        return $this->addNullable($node, $builder->getArray());
    }

    private function resolveWildcardNode(RuleTreeNode $node, bool $raw, bool $mayBeCopiedWhole = false): Type
    {
        $wildcard = $node->children[RuleTreeNode::WILDCARD];

        if (! $raw && $wildcard->rule?->flags->excluded === true) {
            return ConstantArrayTypeBuilder::createEmpty()->getArray();
        }

        $type = new ArrayType(
            $node->rule?->type->isList()->yes() ? new IntegerType() : new MixedType(),
            $raw ? $this->resolveRawNode($wildcard) : $this->resolveValidatedNode($wildcard, $mayBeCopiedWhole),
        );

        if (
            $node->rule?->type->isList()->yes()
            && ($raw || $this->isGuaranteedPresent($wildcard, raw: false))
        ) {
            return TypeCombinator::intersect($type, new AccessoryArrayListType());
        }

        return $type;
    }
}

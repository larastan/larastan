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
use PHPStan\Type\TypeUtils;

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
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($nodes as $name => $node) {
            if ($node->rule?->excluded === true) {
                continue;
            }

            $builder->setOffsetValueType(
                new ConstantStringType($name),
                $this->resolveValidatedNode($node),
                ! $this->isValidatedGuaranteedPresent($node),
            );
        }

        if ($unsealed) {
            $builder->makeUnsealed(new MixedType(), new MixedType());
        }

        return $builder->getArray();
    }

    private function resolveRawTopLevel(RuleTreeNode $node): Type
    {
        $type = $this->resolveRawNode($node);

        if (! $this->isRawGuaranteedPresent($node)) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    private function resolveRawNode(RuleTreeNode $node): Type
    {
        if ($node->rule?->possiblyExcluded === true || $node->rule?->degraded === true) {
            return new MixedType();
        }

        if ($this->hasConflictingScalarRule($node)) {
            return $this->resolveLeaf($node);
        }

        if ($node->rule?->allowedKeys !== null) {
            return $this->resolveRawAllowedKeys($node);
        }

        if ($node->degraded) {
            return $this->hasExplicitContainerRule($node) ? $this->resolveLeaf($node) : new MixedType();
        }

        if ($node->children === []) {
            return $this->resolveLeaf($node);
        }

        if (
            ! $this->hasExplicitContainerRule($node)
            && ! $this->hasContainerAlternative($node)
            && ! $this->hasRawGuaranteedNamedDescendant($node)
        ) {
            return $this->resolveLeaf($node);
        }

        if (isset($node->children[RuleTreeNode::WILDCARD])) {
            $type = $this->resolveWildcardNode($node, $this->resolveRawNode(...), true);
        } else {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($node->children as $segment => $child) {
                $builder->setOffsetValueType(
                    new ConstantStringType($segment),
                    $this->resolveRawNode($child),
                    ! $this->isRawGuaranteedPresent($child),
                );
            }

            $builder->makeUnsealed(new MixedType(), new MixedType());
            $type = $builder->getArray();
        }

        if ($node->rule !== null && $node->rule->anyOfRuleGroups !== []) {
            $ruleType = $this->leafType($node);
            $type     = TypeCombinator::intersect($type, $ruleType);

            if (! $this->hasRawGuaranteedNamedDescendant($node)) {
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
        if ($node->rule?->degraded === true) {
            return new MixedType();
        }

        if ($this->hasConflictingScalarRule($node)) {
            return $this->resolveLeaf($node);
        }

        // A parameterized array rule copies the parent unless a separate bare
        // array/list rule prunes it. Excluding every child can also restore copying.
        $mayBeCopiedWhole = $mayBeCopiedWhole
            || ($this->hasExplicitContainerRule($node)
                && ($node->rule?->prunesUnvalidatedKeys !== true || $this->canExcludeAllDescendantRules($node, true)));

        if ($node->rule?->allowedKeys !== null) {
            return $this->resolveValidatedAllowedKeys($node, $mayBeCopiedWhole);
        }

        if ($this->isValidatedParentCopiedWhole($node) && ! $this->hasExplicitContainerRule($node)) {
            return $node->degraded ? $this->resolveLeaf($node) : $this->resolveRawNode($node);
        }

        if ($node->degraded) {
            $type = $this->hasExplicitContainerRule($node)
                ? $this->leafType($node)
                : new ArrayType(new MixedType(), new MixedType());

            return $this->addNullable($node, $type);
        }

        if ($node->children === []) {
            return $this->resolveLeaf($node);
        }

        if ($mayBeCopiedWhole && $node->rule === null && ! $this->hasRawGuaranteedNamedDescendant($node)) {
            return new MixedType();
        }

        if (isset($node->children[RuleTreeNode::WILDCARD])) {
            if ($node->children[RuleTreeNode::WILDCARD]->rule?->excluded === true) {
                return $this->addNullable($node, ConstantArrayTypeBuilder::createEmpty()->getArray());
            }

            return $this->addNullable(
                $node,
                $this->resolveWildcardNode($node, fn (RuleTreeNode $child): Type => $this->resolveValidatedNode($child, $mayBeCopiedWhole), false),
            );
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($node->children as $segment => $child) {
            if ($child->rule?->excluded === true) {
                continue;
            }

            $builder->setOffsetValueType(
                new ConstantStringType($segment),
                $this->resolveValidatedNode($child, $mayBeCopiedWhole),
                ! $this->isValidatedGuaranteedPresent($child),
            );
        }

        if ($mayBeCopiedWhole) {
            $builder->makeUnsealed(new MixedType(), new MixedType());
        }

        return $this->addNullable($node, $builder->getArray());
    }

    private function canExcludeAllDescendantRules(RuleTreeNode $node, bool $conditional): bool
    {
        if ($node->degraded) {
            return false;
        }

        foreach ($node->children as $child) {
            if ($child->rule?->excluded === true || ($conditional && $child->rule?->possiblyExcluded === true)) {
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

        return ! $node->rule->type->isArray()->yes() && ! $node->rule->type->equals(new MixedType());
    }

    private function hasExplicitContainerRule(RuleTreeNode $node): bool
    {
        return $node->rule?->type->isArray()->yes() ?? false;
    }

    private function hasContainerAlternative(RuleTreeNode $node): bool
    {
        return $node->rule !== null
            && $node->rule->anyOfRuleGroups !== []
            && ! $this->leafType($node)->isArray()->no();
    }

    private function isValidatedParentCopiedWhole(RuleTreeNode $node): bool
    {
        return $node->rule !== null
            && (
                ! $this->hasExplicitContainerRule($node)
                || $node->rule->prunesUnvalidatedKeys === false
            )
            && ($node->children !== [] || $node->degraded);
    }

    /**
     * A node is guaranteed to be present in the raw input when its own rules
     * contain a bare `required`, or when any descendant reachable without crossing
     * a wildcard is guaranteed present: `author.name => required` fails validation
     * when `author` is absent, while `users.*.email => required` passes when
     * `users` is absent (the wildcard expands to zero rules).
     */
    private function isRawGuaranteedPresent(RuleTreeNode $node): bool
    {
        if ($node->rule?->possiblyExcluded === true || $node->rule?->degraded === true) {
            return false;
        }

        if ($node->rule?->required === true && ! $node->rule->possiblyUndefined) {
            return true;
        }

        return $this->hasRawGuaranteedNamedDescendant($node);
    }

    private function hasRawGuaranteedNamedDescendant(RuleTreeNode $node): bool
    {
        foreach ($node->children as $segment => $child) {
            if ($segment !== RuleTreeNode::WILDCARD && $this->isRawGuaranteedPresent($child)) {
                return true;
            }
        }

        return false;
    }

    private function isValidatedGuaranteedPresent(RuleTreeNode $node): bool
    {
        if ($node->rule?->possiblyExcluded === true || $node->rule?->degraded === true) {
            return false;
        }

        if (
            (
                $this->hasConflictingScalarRule($node)
                || $this->isValidatedParentCopiedWhole($node)
                || ($this->hasExplicitContainerRule($node) && $this->canExcludeAllDescendantRules($node, false))
                || ($node->children === [] && ! $node->degraded)
            )
            && $node->rule?->required === true
            && ! $node->rule->possiblyUndefined
        ) {
            return true;
        }

        foreach ($node->children as $segment => $child) {
            if ($segment !== RuleTreeNode::WILDCARD && $this->isValidatedGuaranteedPresent($child)) {
                return true;
            }
        }

        return false;
    }

    private function resolveLeaf(RuleTreeNode $node): Type
    {
        return $this->addNullable($node, $this->leafType($node));
    }

    private function addNullable(RuleTreeNode $node, Type $type): Type
    {
        if (
            $node->rule?->nullable === true
            && ! $node->rule->rejectsNull
            && ! $this->hasRawGuaranteedNamedDescendant($node)
        ) {
            return TypeCombinator::addNull($type);
        }

        return $type;
    }

    private function leafType(RuleTreeNode $node): Type
    {
        if ($node->rule === null) {
            return new MixedType();
        }

        return $this->ruleType($node->rule);
    }

    private function ruleType(ValidationRule $rule, bool $includeNullable = false): Type
    {
        $type = $rule->type;

        if ($rule->constraintType !== null) {
            $type = TypeCombinator::intersect($type, $rule->constraintType);
        }

        foreach ($rule->anyOfRuleGroups as $group) {
            $alternativeTypes = [];

            foreach ($group['rules'] as $alternative) {
                if ($alternative->excluded) {
                    continue;
                }

                $alternativeTypes[] = $this->ruleType($alternative, true);
            }

            if ($alternativeTypes === []) {
                continue;
            }

            if ($group['anyOf']) {
                // AnyOf validates associative input directly, so even scalar list
                // alternatives can pass an array without constraining its other keys.
                $alternativeTypes[] = new ArrayType(new MixedType(), new MixedType());
            }

            $alternativeType = TypeCombinator::union(...$alternativeTypes);

            if ($group['anyOf']) {
                $alternativeType = TypeUtils::toBenevolentUnion($alternativeType);
            }

            $type = TypeCombinator::intersect($type, $alternativeType);
        }

        if ($rule->rejectsNull) {
            $type = TypeCombinator::removeNull($type);
        } elseif ($includeNullable && $rule->nullable) {
            $type = TypeCombinator::addNull($type);
        }

        return $type;
    }

    private function resolveRawAllowedKeys(RuleTreeNode $node): Type
    {
        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($node->rule->allowedKeys ?? [] as $keyType) {
            $key   = (string) $keyType->getValue();
            $child = $node->children[$key] ?? null;

            $builder->setOffsetValueType(
                $keyType,
                $child === null ? new MixedType() : $this->resolveRawNode($child),
                $child === null || ! $this->isRawGuaranteedPresent($child),
            );
        }

        return $this->addNullable($node, $builder->getArray());
    }

    private function resolveValidatedAllowedKeys(RuleTreeNode $node, bool $mayBeCopiedWhole): Type
    {
        $builder     = ConstantArrayTypeBuilder::createEmpty();
        $copiedWhole = $this->isValidatedParentCopiedWhole($node);

        foreach ($node->rule->allowedKeys ?? [] as $keyType) {
            $child = $node->children[(string) $keyType->getValue()] ?? null;

            if ($child?->rule?->excluded === true) {
                continue;
            }

            if (! $mayBeCopiedWhole && $node->children !== [] && $child === null) {
                continue;
            }

            $builder->setOffsetValueType(
                $keyType,
                $child === null ? new MixedType() : $this->resolveValidatedNode($child, $mayBeCopiedWhole),
                $child === null || ! ($copiedWhole ? $this->isRawGuaranteedPresent($child) : $this->isValidatedGuaranteedPresent($child)),
            );
        }

        return $this->addNullable($node, $builder->getArray());
    }

    /** @param callable(RuleTreeNode): Type $resolveItem */
    private function resolveWildcardNode(RuleTreeNode $node, callable $resolveItem, bool $raw): Type
    {
        $wildcard = $node->children[RuleTreeNode::WILDCARD];
        $type     = new ArrayType(
            $node->rule?->type->isList()->yes() ? new IntegerType() : new MixedType(),
            $resolveItem($wildcard),
        );

        if (
            $node->rule?->type->isList()->yes()
            && ($raw || $this->isValidatedGuaranteedPresent($wildcard))
        ) {
            return TypeCombinator::intersect($type, new AccessoryArrayListType());
        }

        return $type;
    }
}

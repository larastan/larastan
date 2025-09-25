<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\CollectionOf;

use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

class CollectionOfType implements CompoundType, LateResolvableType
{
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;

    public function __construct(private Type $type, private CollectionHelper $collectionHelper)
    {
    }

    protected function getResult(): Type
    {
        $results = [];

        foreach ($this->type->getObjectClassNames() as $className) {
            $results[] = $this->collectionHelper->determineCollectionClass($className);
        }

        return TypeCombinator::union(...$results);
    }

    public function isResolvable(): bool
    {
        return ! TypeUtils::containsTemplateType($this->type);
    }

    /** @inheritDoc */
    public function getReferencedClasses(): array
    {
        return $this->type->getReferencedClasses();
    }

    /** @inheritDoc */
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return $this->type->getReferencedTemplateTypes($positionVariance);
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->type->equals($type->type);
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'collection-of<' . $this->type->describe($level) . '>';
    }

    /** @param callable(Type): Type $cb */
    public function traverse(callable $cb): Type
    {
        $type = $cb($this->type);

        if ($this->type === $type) {
            return $this;
        }

        return new self($type, $this->collectionHelper);
    }

    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        if (! $right instanceof self) {
            return $this;
        }

        $type = $cb($this->type, $right->type);

        if ($this->type === $type) {
            return $this;
        }

        return new self($type, $this->collectionHelper);
    }

    public function toPhpDocNode(): TypeNode
    {
        return new GenericTypeNode(new IdentifierTypeNode('collection-of'), [$this->type->toPhpDocNode()]);
    }
}

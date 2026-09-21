<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\CollectionOf;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

use function array_merge;

class CollectionOfType implements CompoundType, LateResolvableType
{
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;

    public function __construct(private Type $type, private CollectionHelper $collectionHelper, private Type|null $keyType = null)
    {
    }

    protected function getResult(): Type
    {
        $results = [];

        foreach (TypeUtils::flattenTypes($this->type) as $modelType) {
            foreach ($modelType->getObjectClassNames() as $className) {
                if (! (new ObjectType(Model::class))->isSuperTypeOf(new ObjectType($className))->yes()) {
                    continue;
                }

                $results[] = $this->collectionHelper->determineCollectionClass(
                    $className,
                    $modelType,
                    $this->keyType ?? new BenevolentUnionType([new IntegerType(), new StringType()]),
                );
            }
        }

        return TypeCombinator::union(...$results);
    }

    public function isResolvable(): bool
    {
        return ! TypeUtils::containsTemplateType($this->type)
            && ($this->keyType === null || ! TypeUtils::containsTemplateType($this->keyType));
    }

    /** @inheritDoc */
    public function getReferencedClasses(): array
    {
        return array_merge($this->type->getReferencedClasses(), $this->keyType?->getReferencedClasses() ?? []);
    }

    /** @inheritDoc */
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return array_merge(
            $this->type->getReferencedTemplateTypes($positionVariance),
            $this->keyType?->getReferencedTemplateTypes($positionVariance) ?? [],
        );
    }

    public function equals(Type $type): bool
    {
        if (! $type instanceof self || ! $this->type->equals($type->type)) {
            return false;
        }

        if ($this->keyType === null || $type->keyType === null) {
            return $this->keyType === $type->keyType;
        }

        return $this->keyType->equals($type->keyType);
    }

    public function describe(VerbosityLevel $level): string
    {
        if ($this->keyType !== null) {
            return 'collection-of<' . $this->keyType->describe($level) . ', ' . $this->type->describe($level) . '>';
        }

        return 'collection-of<' . $this->type->describe($level) . '>';
    }

    /** @param callable(Type): Type $cb */
    public function traverse(callable $cb): Type
    {
        $type    = $cb($this->type);
        $keyType = $this->keyType !== null ? $cb($this->keyType) : null;

        if ($this->type === $type && $this->keyType === $keyType) {
            return $this;
        }

        return new self($type, $this->collectionHelper, $keyType);
    }

    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        if (! $right instanceof self) {
            return $this;
        }

        $type    = $cb($this->type, $right->type);
        $keyType = $this->keyType !== null && $right->keyType !== null ? $cb($this->keyType, $right->keyType) : $this->keyType;

        if ($this->type === $type && $this->keyType === $keyType) {
            return $this;
        }

        return new self($type, $this->collectionHelper, $keyType);
    }

    public function toPhpDocNode(): TypeNode
    {
        $genericTypes = $this->keyType !== null
            ? [$this->keyType->toPhpDocNode(), $this->type->toPhpDocNode()]
            : [$this->type->toPhpDocNode()];

        return new GenericTypeNode(new IdentifierTypeNode('collection-of'), $genericTypes);
    }
}

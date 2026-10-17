<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\BuilderOf;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Types\RelationOf\RelationOfType;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

use function array_merge;
use function count;
use function explode;
use function strtolower;

class BuilderOfType implements CompoundType, LateResolvableType
{
    use LateResolvableTypeTrait;

    /** @var array{Type|null, bool}|null */
    private array|null $resolvedRelations = null;

    public function __construct(private Type $type, private BuilderHelper $builderHelper, private Type|null $relationType = null, private bool $aggregate = false)
    {
    }

    protected function getResult(): Type
    {
        [$relationType, $unknownModel] = $this->resolveRelations();

        // A dynamic relation may lead to any model's (possibly custom) builder.
        // Do not infer the declaring model for aggregate constraints.
        if ($this->aggregate && $relationType === null && $this->relationType !== null && $this->relationType->getConstantStrings() === []) {
            return new GenericObjectType(
                Builder::class,
                [new MixedType()],
                null,
                null,
                [TemplateTypeVariance::createBivariant()],
            );
        }

        // A path that failed on an unknown model says nothing about the declaring model.
        // Alternatives that did resolve win outright, so an unknown one is discarded like a missing one.
        $relatedType = $relationType?->getTemplateType(Relation::class, 'TRelatedModel')
            ?? ($unknownModel ? new ObjectType(Model::class) : $this->type);

        $results = [];

        foreach (TypeUtils::flattenTypes($relatedType) as $modelType) {
            foreach ($modelType->getObjectClassNames() as $className) {
                $builderType = $this->builderHelper->determineBuilderClass($className, $modelType);

                if ($builderType === null) {
                    continue;
                }

                $results[] = $builderType;
            }
        }

        $result = TypeCombinator::union(...$results);

        // Each key may select a different relationship, matching the existing
        // benevolent-union convention for eager-loading callback parameters.
        return $this->aggregate ? TypeUtils::toBenevolentUnion($result) : $result;
    }

    /** @internal */
    public function resolveRelationType(): Type|null
    {
        [$relationType] = $this->resolveRelations();

        return $relationType;
    }

    /** @return array{Type|null, bool} the relationship type, and whether a path failed on an unknown model */
    private function resolveRelations(): array
    {
        return $this->resolvedRelations ??= $this->relationType === null
            ? [null, false]
            : (new RelationOfType(
                $this->type,
                $this->aggregate ? $this->stripAggregateAliases($this->relationType) : $this->relationType,
            ))->resolveRelations();
    }

    /**
     * Alias syntax belongs exclusively to aggregates. Traverse only the
     * alternatives of a union/intersection, never generic type parameters.
     */
    private function stripAggregateAliases(Type $relation): Type
    {
        return TypeTraverser::map($relation, static function (Type $type, callable $traverse): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if (! $type instanceof ConstantStringType) {
                return $type;
            }

            // Mirror Laravel withAggregate(): three space-separated segments.
            $segments = explode(' ', $type->getValue());

            if (count($segments) !== 3 || strtolower($segments[1]) !== 'as') {
                return $type;
            }

            return new ConstantStringType($segments[0]);
        });
    }

    public function isResolvable(): bool
    {
        return ! TypeUtils::containsTemplateType($this->type)
            && ($this->relationType === null || ! TypeUtils::containsTemplateType($this->relationType));
    }

    /** @inheritDoc */
    public function getReferencedClasses(): array
    {
        return array_merge($this->type->getReferencedClasses(), $this->relationType?->getReferencedClasses() ?? []);
    }

    /** @inheritDoc */
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return array_merge(
            $this->type->getReferencedTemplateTypes($positionVariance),
            $this->relationType?->getReferencedTemplateTypes($positionVariance) ?? [],
        );
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->aggregate === $type->aggregate && $this->type->equals($type->type)
            && ($this->relationType === null
                ? $type->relationType === null
                : $type->relationType !== null && $this->relationType->equals($type->relationType));
    }

    public function describe(VerbosityLevel $level): string
    {
        return ($this->aggregate ? 'aggregate-builder-of<' : 'builder-of<') . $this->type->describe($level)
            . ($this->relationType === null ? '' : ', ' . $this->relationType->describe($level)) . '>';
    }

    /** @param callable(Type): Type $cb */
    public function traverse(callable $cb): Type
    {
        $type         = $cb($this->type);
        $relationType = $this->relationType === null ? null : $cb($this->relationType);

        if ($this->type === $type && $this->relationType === $relationType) {
            return $this;
        }

        return new self($type, $this->builderHelper, $relationType, $this->aggregate);
    }

    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        if (! $right instanceof self || $this->aggregate !== $right->aggregate || ($this->relationType === null) !== ($right->relationType === null)) {
            return $this;
        }

        $type         = $cb($this->type, $right->type);
        $relationType = $this->relationType !== null && $right->relationType !== null
            ? $cb($this->relationType, $right->relationType)
            : null;

        if ($this->type === $type && $this->relationType === $relationType) {
            return $this;
        }

        return new self($type, $this->builderHelper, $relationType, $this->aggregate);
    }

    public function toPhpDocNode(): TypeNode
    {
        $types = [$this->type->toPhpDocNode()];

        if ($this->relationType !== null) {
            $types[] = $this->relationType->toPhpDocNode();
        }

        return new GenericTypeNode(new IdentifierTypeNode($this->aggregate ? 'aggregate-builder-of' : 'builder-of'), $types);
    }

    public function generalize(GeneralizePrecision $precision): Type
    {
        return $this->traverse(static fn (Type $type) => $type->generalize($precision));
    }
}

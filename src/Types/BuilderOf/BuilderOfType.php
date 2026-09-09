<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\BuilderOf;

use Illuminate\Database\Eloquent\Relations\Relation;
use Larastan\Larastan\Methods\BuilderHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\CompoundType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

use function array_merge;
use function explode;

class BuilderOfType implements CompoundType, LateResolvableType
{
    use LateResolvableTypeTrait;

    public function __construct(private Type $type, private BuilderHelper $builderHelper, private Type|null $relationType = null)
    {
    }

    protected function getResult(): Type
    {
        $results     = [];
        $relatedType = $this->resolveRelationType()?->getTemplateType(Relation::class, 'TRelatedModel') ?? $this->type;

        foreach (TypeUtils::flattenTypes($relatedType) as $modelType) {
            foreach ($modelType->getObjectClassNames() as $className) {
                $builderType = $this->builderHelper->determineBuilderClass($className, $modelType);

                if ($builderType === null) {
                    continue;
                }

                $results[] = $builderType;
            }
        }

        return TypeCombinator::union(...$results);
    }

    /** @internal */
    public function resolveRelationType(): Type|null
    {
        if (
            $this->relationType === null
            || TypeUtils::containsTemplateType($this->relationType)
            || ! $this->relationType->isConstantScalarValue()->yes()
        ) {
            return null;
        }

        $results = [];

        foreach ($this->relationType->getConstantStrings() as $relation) {
            $relatedType = $this->type;

            foreach (explode('.', explode(':', $relation->getValue(), 2)[0]) as $relationName) {
                $relations = [];

                foreach (TypeUtils::flattenTypes($relatedType) as $modelType) {
                    if (! $modelType->hasMethod($relationName)->yes()) {
                        continue;
                    }

                    $method     = $modelType->getMethod($relationName, new OutOfClassScope());
                    $returnType = ParametersAcceptorSelector::selectFromTypes([], $method->getVariants(), false)->getReturnType();

                    if (! (new ObjectType(Relation::class))->isSuperTypeOf($returnType)->yes()) {
                        continue;
                    }

                    $relations[] = $returnType;
                }

                if ($relations === []) {
                    continue 2;
                }

                $relationType = TypeCombinator::union(...$relations);
                $relatedType  = $relationType->getTemplateType(Relation::class, 'TRelatedModel');
            }

            $results[] = $relationType;
        }

        return $results === [] ? null : TypeCombinator::union(...$results);
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
        return $type instanceof self && $this->type->equals($type->type)
            && ($this->relationType === null
                ? $type->relationType === null
                : $type->relationType !== null && $this->relationType->equals($type->relationType));
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'builder-of<' . $this->type->describe($level)
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

        return new self($type, $this->builderHelper, $relationType);
    }

    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        if (! $right instanceof self || ($this->relationType === null) !== ($right->relationType === null)) {
            return $this;
        }

        $type         = $cb($this->type, $right->type);
        $relationType = $this->relationType !== null && $right->relationType !== null
            ? $cb($this->relationType, $right->relationType)
            : null;

        if ($this->type === $type && $this->relationType === $relationType) {
            return $this;
        }

        return new self($type, $this->builderHelper, $relationType);
    }

    public function toPhpDocNode(): TypeNode
    {
        $types = [$this->type->toPhpDocNode()];

        if ($this->relationType !== null) {
            $types[] = $this->relationType->toPhpDocNode();
        }

        return new GenericTypeNode(new IdentifierTypeNode('builder-of'), $types);
    }

    public function generalize(GeneralizePrecision $precision): Type
    {
        return $this->traverse(static fn (Type $type) => $type->generalize($precision));
    }
}

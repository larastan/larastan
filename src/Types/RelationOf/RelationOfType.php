<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\RelationOf;

use Illuminate\Database\Eloquent\Relations\Relation;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\CompoundType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;

use function array_fill;
use function array_merge;
use function explode;

final class RelationOfType implements CompoundType, LateResolvableType
{
    use LateResolvableTypeTrait;

    public function __construct(private Type $model, private Type $key)
    {
    }

    protected function getResult(): Type
    {
        $relation = $this->resolveRelationType() ?? new GenericObjectType(
            Relation::class,
            [new MixedType(), new MixedType(), new MixedType()],
            null,
            null,
            array_fill(0, 3, TemplateTypeVariance::createBivariant()),
        );

        // Eager loading creates the relation on a fresh model instance.
        $relation = TypeTraverser::map($relation, static fn (Type $type, callable $traverse): Type => $type instanceof StaticType ? $type->getStaticObjectType() : $traverse($type));

        return TypeUtils::toBenevolentUnion($relation);
    }

    /** @internal */
    public function resolveRelationType(): Type|null
    {
        [$relationType] = $this->resolveRelations();

        return $relationType;
    }

    /**
     * @internal
     *
     * @return array{Type|null, bool} the relationship type, and whether a path failed on an unknown model
     */
    public function resolveRelations(): array
    {
        if (TypeUtils::containsTemplateType($this->key) || ! $this->key->isConstantScalarValue()->yes()) {
            return [null, false];
        }

        $results      = [];
        $unknownModel = false;

        foreach ($this->key->getConstantStrings() as $relation) {
            [$relationType, $unknown] = $this->followRelationPath($relation->getValue());

            $unknownModel = $unknownModel || $unknown;

            if ($relationType === null) {
                continue;
            }

            $results[] = $relationType;
        }

        return [$results === [] ? null : TypeCombinator::union(...$results), $unknownModel];
    }

    /** @return array{Type|null, bool} the relationship type, and whether the path failed on an unknown model */
    private function followRelationPath(string $path): array
    {
        $relatedType  = $this->model;
        $relationType = null;

        foreach (explode('.', explode(':', $path, 2)[0]) as $relationName) {
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
                return [null, $this->isUnknownModel($relatedType)];
            }

            $relationType = TypeCombinator::union(...$relations);
            $relatedType  = $relationType->getTemplateType(Relation::class, 'TRelatedModel');
        }

        return [$relationType, false];
    }

    /**
     * An abstract model is never the one queried, so not finding a relationship on it says nothing
     * about a concrete subclass. `Model` is the extreme case, and one such candidate is enough.
     */
    private function isUnknownModel(Type $type): bool
    {
        $reflections = $type->getObjectClassReflections();

        if ($reflections === []) {
            return true;
        }

        foreach ($reflections as $reflection) {
            if ($reflection->isAbstract()) {
                return true;
            }
        }

        return false;
    }

    public function isResolvable(): bool
    {
        return ! TypeUtils::containsTemplateType($this->model) && ! TypeUtils::containsTemplateType($this->key);
    }

    /** @inheritDoc */
    public function getReferencedClasses(): array
    {
        return array_merge($this->model->getReferencedClasses(), $this->key->getReferencedClasses());
    }

    /** @inheritDoc */
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return array_merge(
            $this->model->getReferencedTemplateTypes($positionVariance),
            $this->key->getReferencedTemplateTypes($positionVariance),
        );
    }

    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->model->equals($type->model) && $this->key->equals($type->key);
    }

    public function describe(VerbosityLevel $level): string
    {
        return 'relation-of<' . $this->model->describe($level) . ', ' . $this->key->describe($level) . '>';
    }

    /** @param callable(Type): Type $cb */
    public function traverse(callable $cb): Type
    {
        $model = $cb($this->model);
        $key   = $cb($this->key);

        return $model === $this->model && $key === $this->key ? $this : new self($model, $key);
    }

    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        if (! $right instanceof self) {
            return $this;
        }

        $model = $cb($this->model, $right->model);
        $key   = $cb($this->key, $right->key);

        return $model === $this->model && $key === $this->key ? $this : new self($model, $key);
    }

    public function toPhpDocNode(): TypeNode
    {
        return new GenericTypeNode(new IdentifierTypeNode('relation-of'), [$this->model->toPhpDocNode(), $this->key->toPhpDocNode()]);
    }

    public function generalize(GeneralizePrecision $precision): Type
    {
        return $this->traverse(static fn (Type $type) => $type->generalize($precision));
    }
}

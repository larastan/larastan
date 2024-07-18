<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Support\CollectionHelper;
use Larastan\Larastan\Types\RelationParserHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function str_ends_with;
use function version_compare;

/** @internal */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension
{
    use Concerns\HasContainer;

    public function __construct(
        private RelationParserHelper $relationParserHelper,
        private CollectionHelper $collectionHelper,
    ) {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
        }

        if (str_ends_with($propertyName, '_count')) {
            $propertyName = Str::before($propertyName, '_count');

            $methodNames = [Str::camel($propertyName), $propertyName];
        } else {
            $methodNames = [$propertyName];
        }

        foreach ($methodNames as $methodName) {
            if (! $classReflection->hasNativeMethod($methodName)) {
                continue;
            }

            $returnType = $classReflection->getNativeMethod($methodName)->getVariants()[0]->getReturnType();

            if ((new ObjectType(Relation::class))->isSuperTypeOf($returnType)->yes()) {
                return true;
            }
        }

        return false;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if (str_ends_with($propertyName, '_count')) {
            return new ModelProperty($classReflection, IntegerRangeType::createAllGreaterThanOrEqualTo(0), new NeverType(), false);
        }

        $methodReflection = $classReflection->getMethod($propertyName, new OutOfClassScope());

        if (version_compare(LARAVEL_VERSION, '11.0.0', '<')) {
            $relationType = $this->getRelationTypeBeforeL11($methodReflection);
        } else {
            $returnType = $methodReflection->getVariants()[0]->getReturnType();

            $relationType = TypeTraverser::map($returnType, function (Type $type, callable $traverse) use ($methodReflection): Type {
                if ($type instanceof UnionType || $type instanceof IntersectionType) {
                    return $traverse($type);
                }

                if (! (new ObjectType(Relation::class))->isSuperTypeOf($type)->yes()) {
                    return $type;
                }

                $related = $type->getTemplateType(Relation::class, 'TRelatedModel');

                // generics not provided, need to new up a Relation with the correct
                // TRelatedModel type so the TResult will populate correctly
                if ($related->getObjectClassNames()[0] === Model::class) {
                    $modelName = $this->relationParserHelper->findModelsInRelationMethod($methodReflection)[0] ?? Model::class;

                    $type = new GenericObjectType($type->getObjectClassNames()[0], [new ObjectType($modelName)]);
                }

                /** @phpstan-ignore phpstanApi.getTemplateType (non-existent template on < L11) */
                return $type->getTemplateType(Relation::class, 'TResult');
            });

            $relationType = $this->collectionHelper->replaceCollectionsInType($relationType);
        }

        return new ModelProperty($classReflection, $relationType, new NeverType(), false);
    }

    private function getRelationTypeBeforeL11(ExtendedMethodReflection $method): Type
    {
        $returnType = $method->getVariants()[0]->getReturnType();

        /** @phpstan-ignore phpstanApi.instanceofType (deprecated, special shortcut we take) */
        if ($returnType instanceof GenericObjectType) {
            $relatedModel = $returnType->getTypes()[0];

            if ($relatedModel->getObjectClassNames() === []) {
                $relatedModelClassNames = [Model::class];
            } else {
                $relatedModelClassNames = $relatedModel->getObjectClassNames();
            }
        } else {
            $modelName              = $this->relationParserHelper->findModelsInRelationMethod($method)[0] ?? Model::class;
            $relatedModel           = new ObjectType($modelName);
            $relatedModelClassNames = [$modelName];
        }

        return TypeTraverser::map($returnType, function (Type $type, callable $traverse) use ($relatedModelClassNames, $relatedModel) {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if ($type->getObjectClassNames() === []) {
                return $traverse($type);
            }

            if ($type instanceof GenericObjectType) {
                $relatedModel           = $type->getTypes()[0];
                $relatedModelClassNames = $relatedModel->getObjectClassNames();
            }

            if (
                (new ObjectType(BelongsToMany::class))->isSuperTypeOf($type)->yes()
                || (new ObjectType(HasMany::class))->isSuperTypeOf($type)->yes()
                || (
                    (new ObjectType(HasManyThrough::class))->isSuperTypeOf($type)->yes()
                    // HasOneThrough extends HasManyThrough
                    && ! (new ObjectType(HasOneThrough::class))->isSuperTypeOf($type)->yes()
                )
                || (new ObjectType(MorphMany::class))->isSuperTypeOf($type)->yes()
                || (new ObjectType(MorphToMany::class))->isSuperTypeOf($type)->yes()
                || Str::contains($type->getObjectClassNames()[0], 'Many') // fallback
            ) {
                $types = [];

                foreach ($relatedModelClassNames as $relatedModelClassName) {
                    $types[] = $this->collectionHelper->determineCollectionClass($relatedModelClassName);
                }

                if ($types !== []) {
                    return TypeCombinator::union(...$types);
                }
            }

            if (
                (new ObjectType(MorphTo::class))->isSuperTypeOf($type)->yes()
                || Str::endsWith($type->getObjectClassNames()[0], 'MorphTo') // fallback
            ) {
                // There was no generic type, or it was just Model
                if ($relatedModel->getObjectClassNames()[0] === Model::class) {
                    return TypeCombinator::addNull($relatedModel);
                }

                $types = [];

                foreach ($relatedModelClassNames as $relatedModelClassName) {
                    $types[] = new ObjectType($relatedModelClassName);
                }

                if ($types !== []) {
                    return TypeCombinator::addNull(TypeCombinator::union(...$types));
                }
            }

            return new UnionType([
                $relatedModel,
                new NullType(),
            ]);
        });
    }
}

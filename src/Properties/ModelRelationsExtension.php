<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Reflection\ReflectionHelper;
use NunoMaduro\Larastan\Support\CollectionHelper;
use NunoMaduro\Larastan\Types\RelationParserHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

/**
 * @internal
 */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension
{
    use Concerns\HasContainer;

    public function __construct(private RelationParserHelper $relationParserHelper, private CollectionHelper $collectionHelper)
    {
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
            $propertyName = Str::camel(Str::before($propertyName, '_count'));
        }

        $hasNativeMethod = $classReflection->hasNativeMethod($propertyName);

        if (! $hasNativeMethod) {
            return false;
        }

        $returnType = ParametersAcceptorSelector::selectSingle($classReflection->getNativeMethod($propertyName)->getVariants())->getReturnType();

        if (! (new ObjectType(Relation::class))->isSuperTypeOf($returnType)->yes()) {
            return false;
        }

        return true;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if (str_ends_with($propertyName, '_count')) {
            return new ModelProperty($classReflection, IntegerRangeType::createAllGreaterThanOrEqualTo(0), new NeverType(), false);
        }

        $method = $classReflection->getMethod($propertyName, new OutOfClassScope());

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if ($returnType instanceof GenericObjectType) { // @phpstan-ignore-line This is a special shortcut we take
            $relatedModel = $returnType->getTypes()[0];

            if ($relatedModel->getObjectClassNames() === []) {
                $relatedModelClassNames = [Model::class];
            } else {
                $relatedModelClassNames = $relatedModel->getObjectClassNames();
            }
        } else {
            $modelName = $this->relationParserHelper->findRelatedModelInRelationMethod($method) ?? Model::class;
            $relatedModel = new ObjectType($modelName);
            $relatedModelClassNames = [$modelName];
        }

        $relationType = TypeTraverser::map($returnType, function (Type $type, callable $traverse) use ($relatedModelClassNames, $relatedModel) {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if ($type->getObjectClassNames() === []) {
                return $traverse($type);
            }

            if ($type instanceof GenericObjectType) {
                $relatedModel = $type->getTypes()[0];
                $relatedModelClassNames = $relatedModel->getObjectClassNames();
            }

            if (Str::contains($type->getObjectClassNames()[0], 'Many')) {
                $types = [];

                foreach ($relatedModelClassNames as $relatedModelClassName) {
                    $types[] = $this->collectionHelper->determineCollectionClass($relatedModelClassName);
                }

                if ($types !== []) {
                    return TypeCombinator::union(...$types);
                }
            }

            if (Str::endsWith($type->getObjectClassNames()[0], 'MorphTo')) {
                // There was no generic type, or it was just Model
                // so we will return mixed to avoid errors.
                if ($relatedModel->getObjectClassNames()[0] === Model::class) {
                    return new MixedType();
                }

                $types = [];

                foreach ($relatedModelClassNames as $relatedModelClassName) {
                    $types[] = new ObjectType($relatedModelClassName);
                }

                if ($types !== []) {
                    return TypeCombinator::union(...$types);
                }
            }

            return new UnionType([
                $relatedModel,
                new NullType(),
            ]);
        });

        return new ModelProperty($classReflection, $relationType, new NeverType(), false);
    }
}

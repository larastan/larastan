<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use NunoMaduro\Larastan\Reflection\ReflectionHelper;
use NunoMaduro\Larastan\Types\RelationParserHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;
use PHPStan\Type\UnionType;

/**
 * @internal
 */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension
{
    use Concerns\HasContainer;

    /** @var RelationParserHelper */
    private $relationParserHelper;

    /** @var BuilderHelper */
    private $builderHelper;

    public function __construct(
        RelationParserHelper $relationParserHelper,
        BuilderHelper $builderHelper)
    {
        $this->relationParserHelper = $relationParserHelper;
        $this->builderHelper = $builderHelper;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if (ReflectionHelper::hasPropertyTag($classReflection, $propertyName)) {
            return false;
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
        $method = $classReflection->getMethod($propertyName, new OutOfClassScope());

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if ($returnType instanceof GenericObjectType) {
            /** @var ObjectType $relatedModelType */
            $relatedModelType = $returnType->getTypes()[0];
            $relatedModelClassName = $relatedModelType->getClassName();
        } else {
            $relatedModelClassName = $this
                ->relationParserHelper
                ->findRelatedModelInRelationMethod($method);
        }

        if ($relatedModelClassName === null) {
            $relatedModelClassName = Model::class;
        }

        $relatedModel = new ObjectType($relatedModelClassName);

        $relationType = TypeTraverser::map($returnType, function (Type $type, callable $traverse) use ($relatedModelClassName, $relatedModel) {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if ($type instanceof TypeWithClassName) {
                if ($type instanceof GenericObjectType) {
                    /** @var ObjectType $relatedModel */
                    $relatedModel = $type->getTypes()[0];
                    $relatedModelClassName = $relatedModel->getClassName();
                }

                if (Str::contains($type->getClassName(), 'Many')) {
                    $collectionClass = $this->builderHelper->determineCollectionClassName($relatedModelClassName);

                    return new GenericObjectType($collectionClass, [new IntegerType(), $relatedModel]);
                }

                if (Str::endsWith($type->getClassName(), 'MorphTo')) {
                    return new MixedType();
                }

                return new UnionType([
                    $relatedModel,
                    new NullType(),
                ]);
            }

            return $traverse($type);
        });

        return new ModelProperty($classReflection, $relationType, new NeverType(), false);
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Types\RelationParserHelper;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;

/**
 * @internal
 */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;
    use Concerns\HasContainer;

    /** @var RelationParserHelper */
    private $relationParserHelper;

    public function __construct(RelationParserHelper $relationParserHelper)
    {
        $this->relationParserHelper = $relationParserHelper;
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        if (Str::endsWith($propertyName, '_count')) {
            $propertyName = Str::beforeLast($propertyName, '_count');
        }

        return $classReflection->hasNativeMethod($propertyName);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        if (Str::endsWith($propertyName, '_count')) {
            return new ModelProperty($classReflection, IntegerRangeType::fromInterval(0, null), IntegerRangeType::fromInterval(0, null), false);
        }

        $method = $classReflection->getNativeMethod($propertyName);

        if (! (new ObjectType(Relation::class))->isSuperTypeOf($method->getVariants()[0]->getReturnType())->yes()) {
            return new DummyPropertyReflection();
        }

        /** @var ObjectType $relationType */
        $relationType = $method->getVariants()[0]->getReturnType();
        $relationClass = $relationType->getClassName();

        /** @var string $filename */
        $filename = $classReflection->getNativeReflection()->getMethod($propertyName)->getFileName();
        $relatedModelClass = $this->relationParserHelper->findRelatedModelInRelationMethod(
            $filename,
            $propertyName
        );

        if ($relatedModelClass === null) {
            $relatedModelClass = Model::class;
        }

        $relatedModel = new ObjectType($relatedModelClass);

        if (Str::contains($relationClass, 'Many')) {
            return new ModelProperty(
                $classReflection,
                new GenericObjectType(Collection::class, [$relatedModel]),
                new NeverType(), false
            );
        }

        if (Str::endsWith($relationClass, 'MorphTo')) {
            return new ModelProperty($classReflection, new UnionType([
                new ObjectType(Model::class),
                new MixedType(),
            ]), new NeverType(), false);
        }

        return new ModelProperty($classReflection, new UnionType([
            $relatedModel,
            new NullType(),
        ]), new NeverType(), false);
    }
}

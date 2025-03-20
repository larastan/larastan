<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Larastan\Larastan\Concerns;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Support\CollectionHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function str_ends_with;

/** @internal */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension
{
    use Concerns\HasContainer;

    public function __construct(private CollectionHelper $collectionHelper)
    {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->is(Model::class)) {
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

        $returnType = $classReflection->getMethod($propertyName, new OutOfClassScope())
            ->getVariants()[0]
            ->getReturnType();

        $relationType = TypeTraverser::map($returnType, static function (Type $type, callable $traverse): Type {
            if ($type instanceof UnionType || $type instanceof IntersectionType) {
                return $traverse($type);
            }

            if (! (new ObjectType(Relation::class))->isSuperTypeOf($type)->yes()) {
                return $type;
            }

            return $type->getTemplateType(Relation::class, 'TResult');
        });

        $relationType = $this->collectionHelper->replaceCollectionsInType($relationType);

        return new ModelProperty($classReflection, $relationType, new NeverType(), false);
    }
}

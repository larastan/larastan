<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Properties;

use Illuminate\Support\Str;
use PHPStan\Type\MixedType;
use PHPStan\Type\UnionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\IterableType;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Type\IntersectionType;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPStan\Reflection\Dummy\DummyPropertyReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;

/**
 * @internal
 */
final class ModelRelationsExtension implements PropertiesClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;
    use Concerns\HasContainer;

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        if (! $classReflection->isSubclassOf(Model::class)) {
            return false;
        }

        return $classReflection->hasNativeMethod($propertyName);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $method = $classReflection->getNativeMethod($propertyName);

        if (! (new ObjectType(Relation::class))->isSuperTypeOf($method->getVariants()[0]->getReturnType())->yes()) {
            return new DummyPropertyReflection();
        }

        /** @var ObjectType $relationType */
        $relationType = $method->getVariants()[0]->getReturnType();
        $relationClass = $relationType->getClassName();
        $relatedModel = get_class($this->getContainer()->make($classReflection->getName())->{$propertyName}()->getRelated());

        if (Str::contains($relationClass, 'Many')) {
            return new ModelRelationProperty(
                $classReflection,
                new IntersectionType([
                    new ObjectType(Collection::class),
                    new IterableType(new MixedType(), new ObjectType($relatedModel))
                ]));
        }

        if (Str::endsWith($relationClass, 'MorphTo')) {
            return new ModelRelationProperty($classReflection, new UnionType([
                new ObjectType(Model::class),
                new MixedType()
            ]));
        }

        return new ModelRelationProperty($classReflection, new ObjectType($relatedModel));
    }
}

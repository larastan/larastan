<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use ReflectionException;

use function method_exists;

/** @internal */
final class ModelHelper
{
    /** @var array<string, Model> */
    private static array $instances = [];

    /**
     * Instances are memoized per class, so callers must treat them as read-only.
     *
     * @throws ReflectionException
     */
    public static function newInstanceWithoutConstructor(ClassReflection $classReflection): Model
    {
        $className = $classReflection->getName();

        if (isset(self::$instances[$className])) {
            return self::$instances[$className];
        }

        /** @var Model $model */
        $model = $classReflection->getNativeReflection()->newInstanceWithoutConstructor();

        foreach (['initializeHasTimestamps', 'initializeModelAttributes'] as $initializer) {
            if (! method_exists(Model::class, $initializer)) {
                continue;
            }

            // @phpstan-ignore method.notFound, method.notFound (methods only exist since Laravel 13)
            $model->{$initializer}();
        }

        return self::$instances[$className] = $model;
    }
}

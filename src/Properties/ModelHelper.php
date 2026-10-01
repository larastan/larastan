<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Error;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use ReflectionException;

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

        try {
            /** @var Model $model */
            $model = $classReflection->getNativeReflection()->newInstanceWithoutConstructor();
        } catch (Error $error) {
            throw new ReflectionException($error->getMessage(), 0, $error);
        }

        $model->initializeHasTimestamps();
        $model->initializeModelAttributes();

        return self::$instances[$className] = $model;
    }
}

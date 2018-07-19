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

use function gettype;
use PHPStan\Type\Type;
use function get_class;
use function is_object;
use PHPStan\Type\TypehintHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;

/**
 * @internal
 */
final class ContainerProperty implements PropertyReflection
{
    /**
     * @var \PHPStan\Reflection\ClassReflection
     */
    private $declaringClass;

    /**
     * @var mixed
     */
    private $concrete;

    /**
     * Property constructor.
     *
     * @param \PHPStan\Reflection\ClassReflection $declaringClass
     * @param mixed $concrete
     */
    public function __construct(ClassReflection $declaringClass, $concrete)
    {
        $this->declaringClass = $declaringClass;
        $this->concrete = $concrete;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }

    /**
     * {@inheritdoc}
     */
    public function isStatic(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isPrivate(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isPublic(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): Type
    {
        $type = is_object($this->concrete) ? get_class($this->concrete) : gettype($this->concrete);

        $reflectionType = new ReflectionTypeContainer($type);

        return TypehintHelper::decideTypeFromReflection(
            $reflectionType,
            null,
            is_object($this->concrete) ? get_class($this->concrete) : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool
    {
        return true;
    }
}

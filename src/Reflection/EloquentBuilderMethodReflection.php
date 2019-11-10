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

namespace NunoMaduro\Larastan\Reflection;

use Illuminate\Database\Eloquent\Builder;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EloquentBuilderMethodReflection implements MethodReflection
{
    /**
     * @var string
     */
    private $methodName;

    /**
     * @var ClassReflection
     */
    private $classReflection;

    /**
     * @var array<int, ParameterReflection>
     */
    private $parameters;

    /**
     * @var Type
     */
    private $returnType;

    /**
     * @var bool
     */
    private $isVariadic;

    public function __construct(string $methodName, ClassReflection $classReflection, array $parameters, ?Type $returnType = null, bool $isVariadic = false)
    {
        $this->methodName = $methodName;
        $this->classReflection = $classReflection;
        $this->parameters = $parameters;
        $this->returnType = $returnType ?? new ObjectType(Builder::class);
        $this->isVariadic = $isVariadic;
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflection;
    }

    public function isStatic(): bool
    {
        return true;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->methodName;
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                $this->parameters,
                $this->isVariadic,
                $this->returnType
            ),
        ];
    }
}

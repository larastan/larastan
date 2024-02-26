<?php

declare(strict_types=1);

namespace Larastan\Larastan\Reflection;

use Illuminate\Database\Eloquent\Builder;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class EloquentBuilderMethodReflection implements MethodReflection
{
    private Type $returnType;

    /** @param  ParameterReflection[] $parameters */
    public function __construct(private string $methodName, private ClassReflection $classReflection, private array $parameters, Type|null $returnType = null, private bool $isVariadic = false)
    {
        $this->returnType = $returnType ?? new ObjectType(Builder::class);
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
     * {@inheritDoc}
     */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(
                TemplateTypeMap::createEmpty(),
                null,
                $this->parameters,
                $this->isVariadic,
                $this->returnType,
            ),
        ];
    }

    public function getDocComment(): string|null
    {
        return null;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getDeprecatedDescription(): string|null
    {
        return null;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getThrowType(): Type|null
    {
        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}

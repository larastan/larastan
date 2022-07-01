<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use Illuminate\Validation\ValidationException;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class Macro implements MethodReflection
{
    /**
     * The is static.
     *
     * @var bool
     */
    private $isStatic = false;

    /**
     * Map of macro methods and thrown exception classes.
     *
     * @var string[]
     */
    private $methodThrowTypeMap = [
        'validate' => ValidationException::class,
        'validateWithBag' => ValidationException::class,
    ];

    public function __construct(private ClassReflection $classReflection, private string $methodName, private ClosureType $closureType)
    {
    }

    public function getDeclaringClass(): ClassReflection
    {
        return $this->classReflection;
    }

    public function isPrivate(): bool
    {
        return false;
    }

    public function isPublic(): bool
    {
        return true;
    }

    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * Set the is static value.
     *
     * @param  bool  $isStatic
     * @return void
     */
    public function setIsStatic(bool $isStatic): void
    {
        $this->isStatic = $isStatic;
    }

    /**
     * {@inheritdoc}
     */
    public function getDocComment(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->methodName;
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }

    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(TemplateTypeMap::createEmpty(), null, $this->closureType->getParameters(), $this->closureType->isVariadic(), $this->closureType->getReturnType()),
        ];
    }

    public function getDeprecatedDescription(): ?string
    {
        return null;
    }

    public function getThrowType(): ?Type
    {
        if (array_key_exists($this->methodName, $this->methodThrowTypeMap)) {
            return new ObjectType($this->methodThrowTypeMap[$this->methodName]);
        }

        return null;
    }

    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
}

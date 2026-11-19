<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Validation\ValidationException;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

use function array_key_exists;

final class Macro implements MethodReflection
{
    /**
     * The is static.
     */
    private bool $isStatic = false;

    /**
     * Map of macro methods and thrown exception classes.
     *
     * @var string[]
     */
    private array $methodThrowTypeMap = [
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
     */
    public function setIsStatic(bool $isStatic): void
    {
        $this->isStatic = $isStatic;
    }

    public function getDocComment(): string|null
    {
        return null;
    }

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

    /** @inheritDoc */
    public function getVariants(): array
    {
        return [
            new FunctionVariant(TemplateTypeMap::createEmpty(), null, $this->closureType->getParameters(), $this->closureType->isVariadic(), $this->toParameterTemplates($this->closureType->getReturnType())),
        ];
    }

    /**
     * PROTOTYPE: a body analysed inside the class sees its templates as
     * "argument" types, which call-site substitution skips. Swap them for
     * the templates their class declares so the receiver's bindings apply.
     */
    private function toParameterTemplates(Type $type): Type
    {
        return TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
            if (! $type instanceof TemplateType || ! $type->isArgument()) {
                return $traverse($type);
            }

            $scope = $type->getScope();

            if ($scope->getClassName() === null || $scope->getFunctionName() !== null) {
                return $type;
            }

            return $this->classReflection
                ->getAncestorWithClassName($scope->getClassName())
                ?->getTemplateTypeMap()
                ->getType($type->getName()) ?? $type;
        });
    }

    public function getDeprecatedDescription(): string|null
    {
        return null;
    }

    public function getThrowType(): Type|null
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

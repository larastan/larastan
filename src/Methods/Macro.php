<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Methods;

use function array_map;
use Closure;
use ErrorException;
use Illuminate\Validation\ValidationException;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionType;
use stdClass;

final class Macro implements MethodReflection
{
    /**
     * The parameters.
     *
     * @var ReflectionParameter[]
     */
    private $parameters;

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
    private InitializerExprTypeResolver $exprTypeResolver;

    public function __construct(private ClassReflection $classReflection, private string $methodName, private ReflectionFunction $reflectionFunction, InitializerExprTypeResolver $exprTypeResolver)
    {
        $this->exprTypeResolver = $exprTypeResolver;
        $this->parameters = $this->reflectionFunction->getParameters();

        if ($this->reflectionFunction->isClosure()) {
            try {
                /** @var Closure $closure */
                $closure = $this->reflectionFunction->getClosure();
                Closure::bind($closure, new stdClass);
                // The closure can be bound so it was not explicitly marked as static
            } catch (ErrorException $e) {
                // The closure was explicitly marked as static
                $this->isStatic = true;
            }
        }
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
        return $this->reflectionFunction->getDocComment() ?: null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->methodName;
    }

    /** @return ParameterReflection[] */
    public function getParameters(): array
    {
        return array_map(function (ReflectionParameter $reflection): ParameterReflection {
            return new class($reflection, $this->reflectionFunction, $this->exprTypeResolver) implements ParameterReflection
            {
                /**
                 * @var ReflectionParameter
                 */
                private $reflection;
                private ReflectionFunction $function;
                private InitializerExprTypeResolver $exprTypeResolver;

                public function __construct(ReflectionParameter $reflection, \ReflectionFunction $function, InitializerExprTypeResolver $exprTypeResolver)
                {
                    $this->reflection = $reflection;
                    $this->function = $function;
                    $this->exprTypeResolver = $exprTypeResolver;
                }

                public function getName(): string
                {
                    return $this->reflection->getName();
                }

                public function isOptional(): bool
                {
                    return $this->reflection->isOptional();
                }

                public function getType(): Type
                {
                    $type = $this->reflection->getType();

                    if ($type === null) {
                        return new MixedType();
                    }

                    return TypehintHelper::decideTypeFromReflection($this->reflection->getType());
                }

                public function passedByReference(): PassedByReference
                {
                    return $this->reflection->isPassedByReference()
                        ? PassedByReference::createCreatesNewVariable()
                        : PassedByReference::createNo();
                }

                public function isVariadic(): bool
                {
                    return $this->reflection->isVariadic();
                }

                public function getDefaultValue(): ?Type
                {
                    if (! $this->reflection->isDefaultValueAvailable()) {
                        return null;
                    }

                    $betterReflection = \PHPStan\BetterReflection\Reflection\ReflectionParameter::createFromClosure($this->function->getClosure(), $this->getName());

                    return $this->exprTypeResolver->getType($betterReflection->getDefaultValueExpr(), InitializerExprContext::fromReflectionParameter(new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter($betterReflection)));
                }
            };
        }, $this->parameters);
    }

    /**
     * Set the parameters value.
     *
     * @param  ReflectionParameter[]  $parameters
     * @return void
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getReturnType(): ?ReflectionType
    {
        return $this->reflectionFunction->getReturnType();
    }

    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflectionFunction->isDeprecated());
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
            new FunctionVariant(TemplateTypeMap::createEmpty(), null, $this->getParameters(), $this->reflectionFunction->isVariadic(), TypehintHelper::decideTypeFromReflection($this->getReturnType())),
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

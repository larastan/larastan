<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Support\Optional;
use Larastan\Larastan\Reflection\DynamicMethodReflection;
use Larastan\Larastan\Support\MacroHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function str_starts_with;

/**
 * Handles `Optional`, whose own `__call` checks for a macro first, then forwards
 * the call to the wrapped value when it is an object and otherwise returns null.
 */
#[AutowiredService]
class OptionalMethodsExtension implements MethodsClassReflectionExtension
{
    public function __construct(private MacroHelper $macroHelper)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->findMethod($classReflection, $methodName) !== null;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->findMethod($classReflection, $methodName) ?? throw new ShouldNotHappenException();
    }

    private function findMethod(ClassReflection $classReflection, string $methodName): MethodReflection|null
    {
        if (! $classReflection->is(Optional::class)) {
            return null;
        }

        $macro = $this->macroHelper->findMacro($classReflection, $methodName);

        if ($macro !== null) {
            return $macro;
        }

        // Magic methods are never routed through __call.
        if (str_starts_with($methodName, '__')) {
            return null;
        }

        $valueType = $classReflection->getActiveTemplateTypeMap()->getType('TValue') ?? new MixedType();

        // Nothing is called on a value that is not an object.
        if ($valueType->isObject()->no()) {
            return $this->dynamicMethod($classReflection, $methodName, new NullType());
        }

        $objectType = TypeCombinator::removeNull($valueType);

        if (! $objectType->isObject()->yes()) {
            return $this->dynamicMethod($classReflection, $methodName, new MixedType());
        }

        if (! $objectType->hasMethod($methodName)->yes()) {
            return null;
        }

        $method = $objectType->getMethod($methodName, new OutOfClassScope());

        if (! $method->isPublic()) {
            return null;
        }

        $isNullable = ! $valueType->isObject()->yes();

        return new DynamicMethodReflection(
            $classReflection,
            $methodName,
            array_map(
                static fn (ParametersAcceptor $variant): ParametersAcceptor => new FunctionVariant(
                    $variant->getTemplateTypeMap(),
                    $variant->getResolvedTemplateTypeMap(),
                    $variant->getParameters(),
                    $variant->isVariadic(),
                    $isNullable ? TypeCombinator::addNull($variant->getReturnType()) : $variant->getReturnType(),
                ),
                $method->getVariants(),
            ),
        );
    }

    /** A method that accepts any arguments, as nothing checks them. */
    private function dynamicMethod(ClassReflection $classReflection, string $methodName, Type $returnType): MethodReflection
    {
        return new DynamicMethodReflection($classReflection, $methodName, [
            new FunctionVariant(TemplateTypeMap::createEmpty(), null, [], true, $returnType),
        ]);
    }
}

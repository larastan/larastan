<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Collection;
use Larastan\Larastan\Support\HigherOrderCollectionProxyHelper;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\TrinaryLogic;
use PHPStan\Type;

use function count;

final class HigherOrderCollectionProxyExtension implements MethodsClassReflectionExtension
{
    public function __construct(private HigherOrderCollectionProxyHelper $higherOrderCollectionProxyHelper)
    {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        return $this->higherOrderCollectionProxyHelper->hasPropertyOrMethod($classReflection, $methodName, 'method');
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName,
    ): MethodReflection {
        $activeTemplateTypeMap = $classReflection->getActiveTemplateTypeMap();

        /** @var Type\Constant\ConstantStringType $methodType */
        $methodType = $activeTemplateTypeMap->getType('T');

        /** @var Type\ObjectType $valueType */
        $valueType = $activeTemplateTypeMap->getType('TValue');

        /** @var Type\Type $collectionType */
        $collectionType = $activeTemplateTypeMap->getType('TCollection');

        $collectionClassName = count($collectionType->getObjectClassNames()) === 0
            ? Collection::class
            : $collectionType->getObjectClassNames()[0];

        $modelMethodReflection = $valueType->getMethod($methodName, new OutOfClassScope());

        $modelMethodReturnType = $modelMethodReflection->getVariants()[0]->getReturnType();

        $returnType = $this->higherOrderCollectionProxyHelper->determineReturnType($methodType->getValue(), $valueType, $modelMethodReturnType, $collectionClassName);

        return new class ($classReflection, $methodName, $modelMethodReflection, $returnType) implements MethodReflection
        {
            public function __construct(private ClassReflection $classReflection, private string $methodName, private MethodReflection $modelMethodReflection, private Type\Type $returnType)
            {
            }

            public function getDeclaringClass(): ClassReflection
            {
                return $this->classReflection;
            }

            public function isStatic(): bool
            {
                return false;
            }

            public function isPrivate(): bool
            {
                return false;
            }

            public function isPublic(): bool
            {
                return true;
            }

            public function getDocComment(): string|null
            {
                return null;
            }

            public function getName(): string
            {
                return $this->methodName;
            }

            public function getPrototype(): ClassMemberReflection
            {
                return $this;
            }

            /** @return FunctionVariant[] */
            public function getVariants(): array
            {
                return [
                    new FunctionVariant(
                        $this->modelMethodReflection->getVariants()[0]->getTemplateTypeMap(),
                        $this->modelMethodReflection->getVariants()[0]->getResolvedTemplateTypeMap(),
                        $this->modelMethodReflection->getVariants()[0]->getParameters(),
                        $this->modelMethodReflection->getVariants()[0]->isVariadic(),
                        $this->returnType,
                    ),
                ];
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

            public function getThrowType(): \PHPStan\Type\Type|null
            {
                return null;
            }

            public function hasSideEffects(): TrinaryLogic
            {
                return TrinaryLogic::createMaybe();
            }
        };
    }
}

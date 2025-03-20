<?php

declare(strict_types=1);

namespace Larastan\Larastan\Methods;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function array_key_exists;

class ModelFactoryMethodsClassReflectionExtension implements MethodsClassReflectionExtension
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (! $classReflection->is(Factory::class)) {
            return false;
        }

        $modelType = $classReflection->getActiveTemplateTypeMap()->getType('TModel');

        // Generic type is not specified
        if ($modelType === null) {
            if (! $classReflection->isGeneric() && $classReflection->getParentClass()?->isGeneric()) {
                $modelType = $classReflection->getParentClass()->getActiveTemplateTypeMap()->getType('TModel');
            }
        }

        if ($modelType === null) {
            return false;
        }

        if ($modelType->getObjectClassReflections() !== []) {
            $modelReflection = $modelType->getObjectClassReflections()[0];
        } else {
            $modelReflection = $this->reflectionProvider->getClass(Model::class);
        }

        if ($methodName === 'trashed' && array_key_exists(SoftDeletes::class, $modelReflection->getTraits(true))) {
            return true;
        }

        if (! Str::startsWith($methodName, ['for', 'has'])) {
            return false;
        }

        $relationship = Str::camel(Str::substr($methodName, 3));

        return $modelType->hasMethod($relationship)->yes();
    }

    public function getMethod(
        ClassReflection $classReflection,
        string $methodName,
    ): MethodReflection {
        return new class ($classReflection, $methodName) implements MethodReflection
        {
            public function __construct(private ClassReflection $classReflection, private string $methodName)
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

            /** @return ParametersAcceptor[] */
            public function getVariants(): array
            {
                $returnType     = new ObjectType($this->classReflection->getName());
                $stateParameter = $this->classReflection->getMethod('state', new OutOfClassScope())->getVariants()[0]->getParameters()[0];
                $countParameter = $this->classReflection->getMethod('count', new OutOfClassScope())->getVariants()[0]->getParameters()[0];

                $variants = [
                    new FunctionVariant(TemplateTypeMap::createEmpty(), null, [], false, $returnType),
                ];

                if (Str::startsWith($this->methodName, 'for')) {
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$stateParameter], false, $returnType);
                } else {
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$countParameter], false, $returnType);
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$stateParameter], false, $returnType);
                    $variants[] = new FunctionVariant(TemplateTypeMap::createEmpty(), null, [$countParameter, $stateParameter], false, $returnType);
                }

                return $variants;
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
        };
    }
}

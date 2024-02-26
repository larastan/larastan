<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Larastan\Larastan\Methods\ModelTypeHelper;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/** @internal */
final class BuilderModelFindExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider, private CollectionHelper $collectionHelper)
    {
    }

    public function getClass(): string
    {
        return Builder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();

        if (! Str::startsWith($methodName, 'find')) {
            return false;
        }

        $model = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModelClass');

        if ($model === null || $model->getObjectClassNames() === []) {
            return false;
        }

        return $this->reflectionProvider->getClass(Builder::class)->hasNativeMethod($methodName) ||
            $this->reflectionProvider->getClass(QueryBuilder::class)->hasNativeMethod($methodName);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        if (count($methodCall->getArgs()) < 1) {
            return null;
        }

        /** @var Type $modelClassType */
        $modelClassType = $methodReflection->getDeclaringClass()->getActiveTemplateTypeMap()->getType('TModelClass');

        if ((new ObjectType(Model::class))->isSuperTypeOf($modelClassType)->no()) {
            return null;
        }

        $returnType = $methodReflection->getVariants()[0]->getReturnType();
        $argType    = $scope->getType($methodCall->getArgs()[0]->value);

        if ($argType instanceof MixedType) {
            return $returnType;
        }

        $models = [];

        foreach ($modelClassType->getObjectClassReflections() as $objectClassReflection) {
            $modelName = $objectClassReflection->getName();

            $returnType = ModelTypeHelper::replaceStaticTypeWithModel($returnType, $modelName);

            if ($argType->isIterable()->yes()) {
                if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
                    $models[] = $this->collectionHelper->determineCollectionClass($modelName);
                    continue;
                }

                $models[] = TypeCombinator::remove($returnType, new ObjectType($modelName));
            } else {
                $models[] = TypeCombinator::remove(
                    TypeCombinator::remove(
                        $returnType,
                        new ArrayType(new MixedType(), $modelClassType),
                    ),
                    new ObjectType(Collection::class),
                );
            }
        }

        return count($models) > 0 ? TypeCombinator::union(...$models) : null;
    }
}

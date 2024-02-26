<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;
use function in_array;

/** @internal */
final class ModelFindExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(private ReflectionProvider $reflectionProvider, private CollectionHelper $collectionHelper)
    {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $methodName = $methodReflection->getName();

        if (! Str::startsWith($methodName, 'find')) {
            return false;
        }

        return $this->reflectionProvider->getClass(Builder::class)->hasNativeMethod($methodName) ||
            $this->reflectionProvider->getClass(QueryBuilder::class)->hasNativeMethod($methodName);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        if (count($methodCall->getArgs()) < 1) {
            return new ErrorType();
        }

        $class = $methodCall->class;

        if ($class instanceof Name) {
            $modelNames = [$scope->resolveName($class)];
        } else {
            $type = $scope->getType($class);

            if ($type->getObjectClassNames() !== []) {
                $modelNames = $type->getObjectClassNames();
            } elseif (
                $type->isClassStringType()->yes() &&
                count($type->getReferencedClasses()) > 0
            ) {
                $modelNames = $type->getReferencedClasses();
            } else {
                return new ErrorType();
            }
        }

        $types = [];

        foreach ($modelNames as $modelName) {
            $returnType = $methodReflection->getVariants()[0]->getReturnType();
            $argType    = $scope->getType($methodCall->getArgs()[0]->value);

            if ($argType->isIterable()->yes()) {
                if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
                    $types[] = $this->collectionHelper->determineCollectionClass($modelName);
                    continue;
                }

                $types[] = TypeCombinator::remove($returnType, new ObjectType($modelName));
                continue;
            }

            if ($argType instanceof MixedType) {
                $types[] = $returnType;
            } else {
                $types[] = TypeCombinator::remove(
                    TypeCombinator::remove(
                        $returnType,
                        new ArrayType(new MixedType(), new ObjectType($modelName)),
                    ),
                    new ObjectType(Collection::class),
                );
            }
        }

        return TypeCombinator::union(...$types);
    }
}

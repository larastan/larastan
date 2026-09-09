<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Support\CollectionHelper;
use Larastan\Larastan\Types\BuilderOf\BuilderOfType;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function in_array;

/** @internal */
final class ModelDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(
        private BuilderHelper $builderHelper,
        private CollectionHelper $collectionHelper,
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $name = $methodReflection->getName();

        if ($name === '__construct') {
            return false;
        }

        // Another extension handles this case
        if (Str::startsWith($name, 'find')) {
            return false;
        }

        return $this->reflectionProvider->getClass(Model::class)->hasNativeMethod($name);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type|null {
        $method = $methodReflection->getDeclaringClass()
            ->getMethod($methodReflection->getName(), $scope);

        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $method->getVariants())->getReturnType();

        if ($returnType instanceof NeverType) {
            return null;
        }

        if ((new ObjectType(EloquentBuilder::class))->isSuperTypeOf($returnType)->yes()) {
            $modelType = $methodCall->class instanceof Name
                ? $scope->resolveTypeByName($methodCall->class)
                : $scope->getType($methodCall->class)->getObjectTypeOrClassStringObjectType();

            if (! (new ObjectType(Model::class))->isSuperTypeOf($modelType)->yes()) {
                return null;
            }

            return new BuilderOfType($modelType instanceof ThisType ? $modelType->getStaticObjectType() : $modelType, $this->builderHelper);
        }

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            $modelType = $methodCall->class instanceof Name
                ? new ObjectType($scope->resolveName($methodCall->class))
                : $scope->getType($methodCall->class)->getObjectTypeOrClassStringObjectType();

            $types = [];

            foreach ($modelType->getObjectClassNames() as $modelName) {
                $types[] = $this->collectionHelper->determineCollectionClass($modelName, TypeCombinator::intersect($modelType, new ObjectType($modelName)));
            }

            if ($types !== []) {
                return TypeCombinator::union(...$types);
            }
        }

        return null;
    }
}

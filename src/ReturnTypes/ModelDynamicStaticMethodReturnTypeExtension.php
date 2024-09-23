<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\StaticType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_intersect;
use function collect;
use function count;
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

        if (in_array($name, ['get', 'hydrate', 'fromQuery'], true)) {
            return true;
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

        if (count(array_intersect([EloquentBuilder::class, QueryBuilder::class, Collection::class], $returnType->getReferencedClasses())) === 0) {
            return null;
        }

        if (count(array_intersect([EloquentBuilder::class], $returnType->getReferencedClasses())) > 0) {
            if ($methodCall->class instanceof Name) {
                $type = $scope->resolveTypeByName($methodCall->class);

                if ($type instanceof ThisType) {
                    $type = new StaticType($type->getClassReflection());
                }

                $returnType = $this->builderHelper->getBuilderTypeForModels($type);
            } elseif ($methodCall->class instanceof Expr) {
                $type = $scope->getType($methodCall->class);

                $returnType = collect($type->getObjectTypeOrClassStringObjectType()->getObjectClassNames())
                    ->filter(function ($class) {
                        if (! $this->reflectionProvider->hasClass($class)) {
                            return false;
                        }

                        return $this->reflectionProvider->getClass($class)->is(Model::class);
                    })
                    ->pipe(function ($models) use ($returnType) {
                        if ($models->isEmpty()) {
                            return $returnType;
                        }

                        return $this->builderHelper->getBuilderTypeForModels($models->all());
                    });
            }
        }

        if (in_array(Collection::class, $returnType->getReferencedClasses(), true)) {
            if ($methodCall->class instanceof Name) {
                $modelNames = [$scope->resolveName($methodCall->class)];
            } else {
                $modelNames = $scope->getType($methodCall->class)->getObjectTypeOrClassStringObjectType()->getObjectClassNames();
            }

            $types = [];

            foreach ($modelNames as $modelName) {
                $types[] = $this->collectionHelper->determineCollectionClass($modelName);
            }

            if ($types !== []) {
                return TypeCombinator::union(...$types);
            }
        }

        return $returnType;
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use NunoMaduro\Larastan\Support\CollectionHelper;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * @internal
 */
final class ModelDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(
        private BuilderHelper $builderHelper,
        private CollectionHelper $collectionHelper,
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Model::class;
    }

    /**
     * {@inheritdoc}
     */
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

        if (! $this->reflectionProvider->getClass(Model::class)->hasNativeMethod($name)) {
            return false;
        }

        $method = $this->reflectionProvider->getClass(Model::class)->getNativeMethod($methodReflection->getName());

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        return count(array_intersect([EloquentBuilder::class, QueryBuilder::class, Collection::class], $returnType->getReferencedClasses())) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $method = $methodReflection->getDeclaringClass()
            ->getMethod($methodReflection->getName(), $scope);

        $returnType = ParametersAcceptorSelector::selectSingle($method->getVariants())->getReturnType();

        if ((count(array_intersect([EloquentBuilder::class], $returnType->getReferencedClasses())) > 0)
            && $methodCall->class instanceof Name
        ) {
            $returnType = new GenericObjectType(
                $this->builderHelper->determineBuilderName($scope->resolveName($methodCall->class)),
                [new ObjectType($scope->resolveName($methodCall->class))]
            );
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

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use Illuminate\Support\LazyCollection;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function in_array;

/**
 * Infers a list for `all()` and `toArray()` called directly on `values()`.
 *
 * A collection type cannot say that its keys are sequential, so the list is
 * only known while `values()` is the receiver of the call.
 */
#[AutowiredService]
class CollectionValuesListDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const SUPPORTED_METHOD_NAMES = ['all', 'toArray'];

    private const FRAMEWORK_CLASS_NAMES = [Collection::class, Enumerable::class, LazyCollection::class];

    public function getClass(): string
    {
        return Enumerable::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), self::SUPPORTED_METHOD_NAMES, true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $valuesCall = $methodCall->var;

        if (! $valuesCall instanceof MethodCall && ! $valuesCall instanceof NullsafeMethodCall) {
            return null;
        }

        if (! $valuesCall->name instanceof Identifier || $valuesCall->name->toLowerString() !== 'values') {
            return null;
        }

        if (! $this->isDeclaredByFramework($scope->getType($valuesCall->var), 'values')) {
            return null;
        }

        if (! $this->isDeclaredByFramework($scope->getType($valuesCall), $methodReflection->getName())) {
            return null;
        }

        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())->getReturnType();

        if (! $returnType->isArray()->yes()) {
            return null;
        }

        return TypeCombinator::intersect(
            new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $returnType->getIterableValueType()),
            new AccessoryArrayListType(),
        );
    }

    /**
     * A subclass may override the method and keep the keys, so only the
     * framework's own implementations are known to return sequential keys.
     */
    private function isDeclaredByFramework(Type $calledOnType, string $methodName): bool
    {
        $classReflections = TypeCombinator::removeNull($calledOnType)->getObjectClassReflections();

        if ($classReflections === []) {
            return false;
        }

        foreach ($classReflections as $classReflection) {
            if (! $classReflection->hasNativeMethod($methodName)) {
                return false;
            }

            $declaringClassName = $classReflection->getNativeMethod($methodName)->getDeclaringClass()->getName();

            if (! in_array($declaringClassName, self::FRAMEWORK_CLASS_NAMES, true)) {
                return false;
            }
        }

        return true;
    }
}

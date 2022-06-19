<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;

class RelationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private BuilderHelper $builderHelper;
    private ReflectionProvider $provider;

    public function __construct(BuilderHelper $builderHelper, ReflectionProvider $provider)
    {
        $this->builderHelper = $builderHelper;
        $this->provider = $provider;
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'hasOne', 'hasOneThrough', 'morphOne',
            'belongsTo', 'morphTo',
            'hasMany', 'hasManyThrough', 'morphMany',
            'belongsToMany', 'morphToMany', 'morphedByMany',
        ], true);
    }

    /**
     * @throws ShouldNotHappenException
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $methodName = $methodReflection->getName();
        $methodArgs = $methodCall->getArgs();
        $numArgs = count($methodArgs);
        /** @var FunctionVariant $functionVariant */
        $functionVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
        $returnType = $functionVariant->getReturnType();

        if (! $returnType instanceof ObjectType) {
            return $returnType;
        }

        if (
            // Special case for MorphTo. `morphTo` can be called without arguments.
            ($methodName !== 'morphTo' && $numArgs < 1) ||
            // Special case for "...Through". `has...Through` must be called with a 2nd parameter for the intermediate model
            (in_array($methodName, ['hasOneThrough', 'hasManyThrough']) && $numArgs < 2)
        ) {
            return $returnType;
        }

        $templateTypes = [];

        // Determine TRelatedModel; this is the 1st parameter, if given, or `Eloquent\Model`
        $relatedModelClassArgType = $numArgs === 0 ?
            new ConstantStringType(Model::class, true) :
            $scope->getType($methodCall->getArgs()[0]->value);
        if (! $relatedModelClassArgType instanceof ConstantStringType) {
            return $returnType;
        }
        $relatedModelClassName = $relatedModelClassArgType->getValue();
        if (! $this->provider->hasClass($relatedModelClassName)) {
            $relatedModelClassName = Model::class;
        }
        $templateTypes[] = new ObjectType($relatedModelClassName);

        // Determine TDeclaringModel; this is the model on whose instance the method is called
        $calledOnType = $scope->getType($methodCall->var);
        if (! $calledOnType instanceof TypeWithClassName) {
            return $returnType;
        }
        $declaringModelClassName = $calledOnType->getClassName();
        $templateTypes[] = new ObjectType($declaringModelClassName);

        // Determine TIntermediateModel for "Through" types; this is the 2nd parameter
        $hasManyThroughType = new ObjectType(HasManyThrough::class);
        if ($hasManyThroughType->isSuperTypeOf($returnType)->yes()) {
            $intermediateModelClassArgType = $scope->getType($methodCall->getArgs()[1]->value);
            if (! $intermediateModelClassArgType instanceof ConstantStringType) {
                return $returnType;
            }
            $intermediateModelClassName = $intermediateModelClassArgType->getValue();
            $templateTypes[] = new ObjectType($intermediateModelClassName);
        }

        // Work-around for a Laravel bug
        // Opposed to other `HasOne...` and `HasMany...` methods,
        // `HasOneThrough` and `HasManyThrough` do not extend a common
        // `HasOneOrManyThrough` base class, but `HasOneThrough` directly
        // extends `HasManyThrough`.
        // This does not only violate Liskov's Substitution Principle but also
        // has the unfortunate side effect that `HasManyThrough` cannot
        // bind the template parameter `TResult` to a Collection, but needs
        // to keep it unbound for `HasOneThrough` to overwrite it.
        // Hence, if `HasManyTrough` is used directly, we must bind the
        // fourth template parameter `TResult` here.
        if ($hasManyThroughType->equals($returnType)) {
            $collectionClassName = $this->builderHelper->determineCollectionClassName($relatedModelClassName);
            $templateTypes[] = new GenericObjectType($collectionClassName, [new IntegerType(), new ObjectType($relatedModelClassName)]);
        }

        return new GenericObjectType($returnType->getClassName(), $templateTypes);
    }
}

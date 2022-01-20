<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
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
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;

class RelationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private ReflectionProvider $provider;

    public function __construct(ReflectionProvider $provider)
    {
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
        /** @var FunctionVariant $functionVariant */
        $functionVariant = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());
        $returnType = $functionVariant->getReturnType();

        if (! $returnType instanceof ObjectType) {
            return $returnType;
        }

        $calledOnType = $scope->getType($methodCall->var);

        if ($calledOnType instanceof StaticType) {
            $calledOnType = new ObjectType($calledOnType->getClassName());
        }

        if (count($methodCall->getArgs()) === 0) {
            // Special case for MorphTo. `morphTo` can be called without arguments.
            if ($methodReflection->getName() === 'morphTo') {
                return new GenericObjectType($returnType->getClassName(), [new ObjectType(Model::class), $calledOnType]);
            }

            return $returnType;
        }

        $argType = $scope->getType($methodCall->getArgs()[0]->value);

        if (! $argType instanceof ConstantStringType) {
            return $returnType;
        }

        $argClassName = $argType->getValue();

        if (! $this->provider->hasClass($argClassName)) {
            $argClassName = Model::class;
        }

        // Special case for BelongsTo. We need to add the child model as a generic type also.
        if ((new ObjectType(BelongsTo::class))->isSuperTypeOf($returnType)->yes()) {
            return new GenericObjectType($returnType->getClassName(), [new ObjectType($argClassName), $calledOnType]);
        }

        return new GenericObjectType($returnType->getClassName(), [new ObjectType($argClassName)]);
    }
}

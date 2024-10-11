<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Larastan\Larastan\Internal\LaravelVersion;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\Type;

use function array_map;
use function array_slice;
use function array_values;
use function count;
use function in_array;

class RelationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private ReflectionProvider $provider)
    {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'hasOne',
            'hasOneThrough',
            'morphOne',
            'belongsTo',
            'morphTo',
            'hasMany',
            'hasManyThrough',
            'morphMany',
            'belongsToMany',
            'morphToMany',
            'morphedByMany',
        ], true);
    }

    /** @throws ShouldNotHappenException */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        $returnType = ParametersAcceptorSelector::selectFromArgs($scope, $methodCall->getArgs(), $methodReflection->getVariants())
            ->getReturnType();

        $classNames = $returnType->getObjectClassNames();

        if (! LaravelVersion::hasLaravel1115Generics()) {
            return $this->getTypeForLaravelLessThan1115($methodReflection, $methodCall, $scope, $returnType, $classNames);
        }

        if (count($classNames) !== 1) {
            return null;
        }

        $isThroughRelation = false;

        if ($methodCall->name instanceof Identifier) {
            $isThroughRelation = in_array($methodCall->name->toString(), ['hasManyThrough', 'hasOneThrough'], strict: true);
        }

        $args = array_slice($methodCall->getArgs(), 0, $isThroughRelation ? 2 : 1);

        $models = array_map(static function ($arg) use ($scope): string {
            $argType     = $scope->getType($arg->value);
            $returnClass = Model::class;

            if ($argType->isClassStringType()->yes()) {
                $classNames = $argType->getClassStringObjectType()->getObjectClassNames();

                if (count($classNames) === 1) {
                    $returnClass = $classNames[0];
                }
            }

            return $returnClass;
        }, array_values($args));

        if (count($models) === 0) {
            // `morphTo` can be called without arguments.
            if ($methodReflection->getName() !== 'morphTo') {
                return null;
            }

            $models = [Model::class];
        }

        $types   = array_map(static fn ($model) => new ObjectType((string) $model), $models);
        $types[] = $scope->getType($methodCall->var);

        return new GenericObjectType($classNames[0], $types);
    }

    /**
     * @param string[] $classNames
     *
     * @throws ShouldNotHappenException
     */
    private function getTypeForLaravelLessThan1115(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
        Type $returnType,
        array $classNames,
    ): Type|null {
        if (count($classNames) !== 1) {
            return null;
        }

        $calledOnType = $scope->getType($methodCall->var);

        if ($calledOnType instanceof StaticType) {
            $calledOnType = new ObjectType($calledOnType->getClassName());
        }

        if (count($methodCall->getArgs()) === 0) {
            // Special case for MorphTo. `morphTo` can be called without arguments.
            if ($methodReflection->getName() === 'morphTo') {
                return new GenericObjectType($classNames[0], [new ObjectType(Model::class), $calledOnType]);
            }

            return null;
        }

        $argType    = $scope->getType($methodCall->getArgs()[0]->value);
        $argStrings = $argType->getConstantStrings();

        if (count($argStrings) !== 1) {
            return null;
        }

        $argClassName = $argStrings[0]->getValue();

        if (! $this->provider->hasClass($argClassName)) {
            $argClassName = Model::class;
        }

        // Special case for BelongsTo. We need to add the child model as a generic type also.
        if ((new ObjectType(BelongsTo::class))->isSuperTypeOf($returnType)->yes()) {
            return new GenericObjectType($classNames[0], [new ObjectType($argClassName), $calledOnType]);
        }

        return new GenericObjectType($classNames[0], [new ObjectType($argClassName)]);
    }
}

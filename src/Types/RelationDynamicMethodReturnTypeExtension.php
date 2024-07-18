<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function array_map;
use function array_slice;
use function array_values;
use function count;
use function in_array;
use function version_compare;

class RelationDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
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

        if (
            version_compare(LARAVEL_VERSION, '11.0.0', '<')
            && ! (new ObjectType(BelongsTo::class))->isSuperTypeOf($returnType)->yes()
        ) {
            // Only BelongsTo has more than one type
            $types = [$types[0]];
        }

        return new GenericObjectType($classNames[0], $types);
    }
}

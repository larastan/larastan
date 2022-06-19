<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\Relation;
use NunoMaduro\Larastan\Concerns\HasContainer;
use NunoMaduro\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class ModelRelationsDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    use HasContainer;

    /** @var BuilderHelper */
    private $builderHelper;

    /** @var RelationParserHelper */
    private $relationParserHelper;

    public function __construct(
        RelationParserHelper $relationParserHelper,
        BuilderHelper $builderHelper
    ) {
        $this->relationParserHelper = $relationParserHelper;
        $this->builderHelper = $builderHelper;
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $variants = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        $returnType = $variants->getReturnType();

        if (! $returnType instanceof ObjectType) {
            return false;
        }

        if (! (new ObjectType(Relation::class))->isSuperTypeOf($returnType)->yes()) {
            return false;
        }

        if (! $methodReflection->getDeclaringClass()->hasNativeMethod($methodReflection->getName())) {
            return false;
        }

        if (count($variants->getParameters()) !== 0) {
            return false;
        }

        if (in_array($methodReflection->getName(), [
            'hasOne', 'hasOneThrough', 'morphOne',
            'belongsTo', 'morphTo',
            'hasMany', 'hasManyThrough', 'morphMany',
            'belongsToMany', 'morphToMany', 'morphedByMany',
        ], true)) {
            return false;
        }

        $relatedModel = $this
            ->relationParserHelper
            ->findRelatedModelInRelationMethod($methodReflection);

        return $relatedModel !== null;
    }

    /**
     * @param  MethodReflection  $methodReflection
     * @param  MethodCall  $methodCall
     * @param  Scope  $scope
     * @return Type
     *
     * @throws ShouldNotHappenException
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        /** @var ObjectType $returnType */
        $returnType = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();

        /** @var string $relatedModelClassName */
        $relatedModelClassName = $this
            ->relationParserHelper
            ->findRelatedModelInRelationMethod($methodReflection);

        $declaringModelClassName = $methodReflection->getDeclaringClass()->getName();

        $templateTypes = [
            new ObjectType($relatedModelClassName),
            new ObjectType($declaringModelClassName),
        ];

        $hasManyThroughType = new ObjectType(HasManyThrough::class);
        if ($hasManyThroughType->isSuperTypeOf($returnType)->yes()) {
            /** @var string $intermediateModelClassName */
            $intermediateModelClassName = $this
                ->relationParserHelper
                ->findIntermediateModelInRelationMethod($methodReflection);
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

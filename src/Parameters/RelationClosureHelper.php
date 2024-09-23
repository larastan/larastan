<?php

declare(strict_types=1);

namespace Larastan\Larastan\Parameters;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Larastan\Larastan\Internal\LaravelVersion;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Types\RelationParserHelper;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

use function array_push;
use function array_shift;
use function collect;
use function count;
use function dd;
use function explode;
use function in_array;
use function is_string;

final class RelationClosureHelper
{
    /** @var list<string> */
    private array $methods = [
        'has',
        'doesntHave',
        'whereHas',
        'withWhereHas',
        'orWhereHas',
        'whereDoesntHave',
        'orWhereDoesntHave',
        'whereRelation',
        'orWhereRelation',
    ];

    /** @var list<string> */
    private array $morphMethods = [
        'hasMorph',
        'doesntHaveMorph',
        'whereHasMorph',
        'orWhereHasMorph',
        'whereDoesntHaveMorph',
        'orWhereDoesntHaveMorph',
        'whereMorphRelation',
        'orWhereMorphRelation',
    ];

    public function __construct(
        private BuilderHelper $builderHelper,
        private RelationParserHelper $relationParserHelper,
    ) {
    }

    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        if (! $methodReflection->getDeclaringClass()->is(EloquentBuilder::class)) {
            return false;
        }

        return in_array($methodReflection->getName(), [...$this->methods, ...$this->morphMethods], strict: true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall|StaticCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        $isMorphMethod = in_array($methodReflection->getName(), $this->morphMethods, strict: true);

        $models = $isMorphMethod
            ? $this->getMorphModels($methodCall, $scope)
            : $this->getModels($methodCall, $scope);

        if (count($models) === 0) {
            return null;
        }

        return new ClosureType([
            new ClosureQueryParameter('query', $this->builderHelper->getBuilderTypeForModels($models)),
            new ClosureQueryParameter('type', $isMorphMethod ? new NeverType() : new StringType()),
        ], new MixedType());
    }

    /** @return array<int, string> */
    private function getMorphModels(MethodCall|StaticCall $methodCall, Scope $scope): array
    {
        $models = null;

        foreach ($methodCall->args as $i => $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                continue;
            }

            if (($i === 1 && $arg->name === null) || $arg->name?->toString() === 'types') {
                $models = $scope->getType($arg->value);
                break;
            }
        }

        if ($models === null) {
            return [];
        }

        return collect($models->getConstantArrays())
            ->flatMap(static fn (ConstantArrayType $t) => $t->getValueTypes())
            ->flatMap(static fn (Type $t) => $t->getConstantStrings())
            ->merge($models->getConstantStrings())
            ->map(static function (ConstantStringType $t) {
                $value = $t->getValue();

                return $value === '*' ? Model::class : $value;
            })
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function getModels(MethodCall|StaticCall $methodCall, Scope $scope): array
    {
        $relations = $this->getRelationsFromMethodCall($methodCall, $scope);

        if (count($relations) === 0) {
            return [];
        }

        if ($methodCall instanceof MethodCall) {
            $calledOnModels = $scope->getType($methodCall->var)
                ->getTemplateType(EloquentBuilder::class, LaravelVersion::getBuilderModelGenericName())
                ->getObjectClassNames();
        } else {
            $calledOnModels = $methodCall->class instanceof Name
                ? [$scope->resolveName($methodCall->class)]
            : dd($scope->getType($methodCall->class))->getReferencedClasses();
        }

        return collect($relations)
            ->flatMap(
                fn ($relation) => is_string($relation)
                    ? $this->getModelsFromStringRelation($calledOnModels, explode('.', $relation), $scope)
                    : $this->getModelsFromRelationReflection($relation),
            )
            ->values()
            ->all();
    }

    /** @return array<int, string|ClassReflection> */
    public function getRelationsFromMethodCall(MethodCall|StaticCall $methodCall, Scope $scope): array
    {
        $relationType = null;

        foreach ($methodCall->args as $arg) {
            if ($arg instanceof VariadicPlaceholder) {
                continue;
            }

            if ($arg->name === null || $arg->name->toString() === 'relation') {
                $relationType = $scope->getType($arg->value);
                break;
            }
        }

        if ($relationType === null) {
            return [];
        }

        return collect([
            ...$relationType->getConstantStrings(),
            ...$relationType->getObjectClassReflections(),
        ])
            ->map(static function ($type) {
                if ($type instanceof ClassReflection) {
                    return $type->is(Relation::class) ? $type : null;
                }

                return $type->getValue();
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param list<string> $calledOnModels
     * @param list<string> $relationParts
     *
     * @return list<string>
     */
    public function getModelsFromStringRelation(
        array $calledOnModels,
        array $relationParts,
        Scope $scope,
    ): array {
        $relationName = array_shift($relationParts);

        if ($relationName === null) {
            return $calledOnModels;
        }

        $models = [];

        foreach ($calledOnModels as $model) {
            $modelType = new ObjectType($model);
            if (! $modelType->hasMethod($relationName)->yes()) {
                continue;
            }

            $relationMethod = $modelType->getMethod($relationName, $scope);
            $relationType   = $relationMethod->getVariants()[0]->getReturnType();

            if (! (new ObjectType(Relation::class))->isSuperTypeOf($relationType)->yes()) {
                continue;
            }

            $relatedModels = $relationType->getTemplateType(Relation::class, 'TRelatedModel')->getObjectClassNames();

            if (count($relatedModels) === 0 || $relatedModels[0] === Model::class) {
                $relatedModels = [$this->relationParserHelper->findModelsInRelationMethod($relationMethod)[0] ?? Model::class];
            }

            array_push($models, ...$this->getModelsFromStringRelation($relatedModels, $relationParts, $scope));
        }

        return $models;
    }

    /** @return list<string> */
    public function getModelsFromRelationReflection(ClassReflection $relation): array
    {
        $relatedModel = $relation->getActiveTemplateTypeMap()->getType('TRelatedModel');

        if ($relatedModel === null) {
            return [];
        }

        return $relatedModel->getObjectClassNames();
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ParameterClosureType;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\ArrayType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\MixedType;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_map;
use function count;
use function explode;
use function in_array;
use function str_contains;
use function strtolower;

class ModelRelationsStaticMethodParameterClosureTypeExtension implements StaticMethodParameterClosureTypeExtension
{
    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $methodReflection->getDeclaringClass()->is(Builder::class) &&
            ($parameter->getName() === 'callback' || ($parameter->getName() === 'column' && ! $parameter->getType()->isCallable()->no())) &&
            in_array($methodReflection->getName(), [
                'has',
                'doesntHave',
                'whereHas',
                'withWhereHas',
                'orWhereHas',
                'whereDoesntHave',
                'orWhereDoesntHave',
                'hasMorph',
                'doesntHaveMorph',
                'whereHasMorph',
                'orWhereHasMorph',
                'whereDoesntHaveMorph',
                'orWhereDoesntHaveMorph',
                'whereRelation',
                'orWhereRelation',
                'whereMorphRelation',
                'orWhereMorphRelation',
            ], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        $class = $methodCall->class;

        if ($class instanceof Name) {
            $modelType = $scope->resolveTypeByName($class);
        } else {
            $modelType = $scope->getType($class);
        }

        $args = $methodCall->getArgs();

        if (count($args) < 1) {
            return null;
        }

        $relationArgStrings = $scope->getType($args[0]->value)->getConstantStrings();

        if (count($relationArgStrings) !== 1) {
            return null;
        }

        $relationName = $relationArgStrings[0]->getValue();

        if (str_contains($relationName, '.')) {
            $relations = explode('.', $relationName);
        } else {
            $relations = [$relationName];
        }

        if (count($modelType->getObjectClassNames()) === 0) {
            return null;
        }

        $modelName = $modelType->getObjectClassNames()[0];

        foreach ($relations as $relation) {
            $relationType = $scope->getType(new MethodCall(new New_(new Name($modelName)), new Identifier($relation)));

            $modelType = $relationType->getTemplateType(Relation::class, 'TRelatedModel');

            if ($modelType instanceof ErrorType) {
                return null;
            }

            if (count($modelType->getObjectClassNames()) === 0) {
                return null;
            }

            $modelName = $modelType->getObjectClassNames()[0];
        }

        $closureParameters = [];

        if (count($args) > 1 && str_contains(strtolower($methodReflection->getName()), 'morph')) {
            $typesArg = $scope->getType($args[1]->value);

            $constantStrings  = $typesArg->getConstantStrings();
            $constantStrings += array_map(static fn (ArrayType $type) => $type->getIterableValueType()->getConstantStrings(), $typesArg->getArrays())[0] ?? [];

            $builders     = [];
            $modelStrings = [];

            foreach ($constantStrings as $constantString) {
                if ($constantString->getValue() === '*') {
                    $modelStrings = [new StringType()];
                    $builders     = [new GenericObjectType(Builder::class, [new MixedType()], variances: [TemplateTypeVariance::createBivariant()])];

                    break;
                }

                if (! $constantString->isClassStringType()->yes()) {
                    continue;
                }

                $modelStrings[] = $constantString;

                $builderType = $scope->getType(new StaticCall(new Name($constantString->getValue()), new Identifier('query')));

                if ($builderType instanceof ErrorType) {
                    continue;
                }

                $builders[] = $builderType;
            }

            if ($builders !== [] && $modelStrings !== []) {
                $closureParameters[] = $this->getParameterReflection(TypeCombinator::union(...$builders), 'query');
                $closureParameters[] = $this->getParameterReflection(TypeCombinator::union(...$modelStrings), 'type');
            } else {
                $closureParameters[] = $this->getParameterReflection($scope->getType(new StaticCall(new Name($modelName), new Identifier('query'))), 'query');
                $closureParameters[] = $this->getParameterReflection(new ConstantStringType($modelName), 'type');
            }
        } else {
            $closureParameters[] = $this->getParameterReflection($scope->getType(new StaticCall(new Name($modelName), new Identifier('query'))), 'query');
        }

        return new ClosureType($closureParameters);
    }

    private function getParameterReflection(Type $type, string $name): ParameterReflection
    {
        return new class ($type, $name) implements ParameterReflection
        {
            public function __construct(
                private Type $type,
                private string $name,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function isOptional(): bool
            {
                return false;
            }

            public function getType(): Type
            {
                return $this->type;
            }

            public function passedByReference(): PassedByReference
            {
                return PassedByReference::createNo();
            }

            public function isVariadic(): bool
            {
                return false;
            }

            public function getDefaultValue(): Type|null
            {
                return null;
            }
        };
    }
}

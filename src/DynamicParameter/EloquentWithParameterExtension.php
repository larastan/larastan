<?php

declare(strict_types=1);

namespace Larastan\Larastan\DynamicParameter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicMethodParameterTypeExtension;
use PHPStan\Type\DynamicStaticMethodParameterTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

use function count;
use function explode;
use function str_contains;

class EloquentWithParameterExtension implements DynamicMethodParameterTypeExtension, DynamicStaticMethodParameterTypeExtension
{
    public function isMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $methodReflection->getDeclaringClass()->is(Builder::class) &&
            $methodReflection->getName() === 'with' &&
            $parameter->getName() === 'relations';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        $calledOnType = $scope->getType($methodCall->var);
        $modelName    = $calledOnType->getTemplateType(Builder::class, 'TModel')->getObjectClassNames();

        if ($modelName === []) {
            $modelName = $calledOnType->getTemplateType(Relation::class, 'TRelatedModel')->getObjectClassNames();
        }

        if ($modelName === []) {
            return null;
        }

        $modelName = $modelName[0];

        return $this->getType($methodCall, $scope, $modelName);
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
    {
        return $methodReflection->getDeclaringClass()->is(Model::class) &&
            $methodReflection->getName() === 'with' &&
            $parameter->getName() === 'relations';
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        ParameterReflection $parameter,
        Scope $scope,
    ): Type|null {
        $class = $methodCall->class;

        if ($class instanceof Name) {
            $modelName = $class->toString();
        } else {
            $modelType = $scope->getType($class);

            if ($modelType->getObjectClassNames() === []) {
                return null;
            }

            $modelName = $modelType->getObjectClassNames()[0];
        }

        return $this->getType($methodCall, $scope, $modelName);
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

    /** @throws ShouldNotHappenException */
    private function getType(StaticCall|MethodCall $methodCall, Scope $scope, string $modelName): Type|null
    {
        $args = $methodCall->getArgs();

        if (count($args) < 1) {
            return null;
        }

        $relationsArg = $scope->getType($args[0]->value);

        if (! $relationsArg->isConstantArray()->yes()) {
            return null;
        }

        $relationsArg = $relationsArg->getConstantArrays()[0];

        $keys   = $relationsArg->getKeyTypes();
        $values = $relationsArg->getValueTypes();

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($keys as $i => $key) {
            if ($key->getConstantStrings() === [] || ! $values[$i]->isCallable()->yes()) {
                $builder->setOffsetValueType($key, $values[$i]);

                continue;
            }

            $relationName = $key->getConstantStrings()[0]->getValue();

            if (str_contains($relationName, '.')) {
                $relations = explode('.', $relationName);
            } else {
                $relations = [$relationName];
            }

            foreach ($relations as $relation) {
                $relationType = $scope->getType(new MethodCall(
                    new New_(new Name($modelName)),
                    new Identifier($relation),
                ));

                $modelType = $relationType->getTemplateType(Relation::class, 'TRelatedModel');

                if ($modelType instanceof ErrorType) {
                    return null;
                }

                if ($modelType->getObjectClassNames() === []) {
                    return null;
                }

                $modelName = $modelType->getObjectClassNames()[0];
            }

            $builder->setOffsetValueType($key, new ClosureType([
                $this->getParameterReflection(
                    $relationType,
                    'query',
                ),
            ]));
        }

        return $builder->getArray();
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use NunoMaduro\Larastan\Properties\ModelPropertyExtension;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * This rule checks for unnecessary heavy operations on the Collection class
 * that could have instead been performed on the Builder class.
 *
 * For example:
 * User::all()->count()
 * could be simplified to:
 * User::count()
 *
 * In addition, this code:
 * User::whereStatus('active')->get()->pluck('id')
 * could be simplified to:
 * User::whereStatus('active')->pluck('id')
 */
class NoUnnecessaryCollectionCallRule implements Rule
{
    /**
     * The method names that can be applied on a Collection, but should be applied on a Builder.
     * @var string[]
     */
    protected const RISKY_METHODS = [
        'count',
        'first',
        'isempty',
        'isnotempty',
        'last',
        'pop',
        'skip',
        'slice',
        'take',
        // Eloquent collection
        'modelKeys',
        'contains',
        'find',
        'findMany',
        'only',
    ];

    /**
     * The method names that can be applied on a Collection, but should in some cases be applied
     * on a builder depending on what parameter is passed to the method.
     * @var string[]
     */
    protected const RISKY_PARAM_METHODS = [
        'average',
        'avg',
        'max',
        'min',
        'pluck',
        'sum',
    ];

    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    protected $reflectionProvider;

    /**
     * @var \NunoMaduro\Larastan\Properties\ModelPropertyExtension
     */
    protected $propertyExtension;

    /**
     * NoRedundantCollectionCallRule constructor.
     * @param ReflectionProvider $reflectionProvider
     * @param ModelPropertyExtension $propertyExtension
     */
    public function __construct(ReflectionProvider $reflectionProvider, ModelPropertyExtension $propertyExtension)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->propertyExtension = $propertyExtension;
    }

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var \PhpParser\Node\Expr\MethodCall $node */
        if (!$node->name instanceof Identifier) {
            return [];
        }

        /** @var \PhpParser\Node\Identifier $name */
        $name = $node->name;

        if ($this->isNotCalledOnCollection($node->var, $scope)) {
            // Method was called not called on a collection, so no errors.
            return [];
        }

        $previousCall = $node->var;

        if (!$this->callIsQuery($previousCall, $scope)) {
            // Previous call wasn't on a Builder, so no errors.
            return [];
        }

        /** @var Node\Expr\MethodCall|Node\Expr\StaticCall $previousCall */
        if (!($previousCall->name instanceof Identifier)) {
            // Previous call was made dynamically e.g. User::query()->{$method}()
            // Can't really analyze it in this scenario so no errors.
            return [];
        }

        if ($this->isRiskyMethod($name)) {
            return [$this->formatError($name->toString())];
        } elseif ($this->isRiskyParamMethod($name)) {
            if (!$this->hasStringAsOnlyArgument($node)) {
                return [];
            }
            /** @var \PhpParser\Node\Scalar\String_ $firstArg */
            $firstArg = $node->args[0]->value;
            $columnName = $firstArg->value;

            /** @var \PHPStan\Type\ObjectType $iterableType */
            $iterableType = $scope->getType($node->var)->getIterableValueType();
            if ((new ObjectType(Model::class))->isSuperTypeOf($iterableType)->yes()) {
                $modelReflection = $this->reflectionProvider->getClass($iterableType->getClassName());

                if (!$this->propertyExtension->hasProperty($modelReflection, $columnName)) {
                    // Not a database property so call couldn't have been improved with a DB query.
                    return [];
                }

                return [$this->formatError($name->toString())];
            }
        }

        return [];
    }

    /**
     * Returns whether the method call was called with a string as its only argument.
     * @param MethodCall $node
     * @return bool
     */
    protected function hasStringAsOnlyArgument(MethodCall $node): bool
    {
        /** @var \PhpParser\Node\Arg[] $args */
        $args = $node->args;

        if (count($args) !== 1) {
            return false;
        }

        if (!($args[0]->value instanceof Node\Scalar\String_)) {
            return false;
        }

        return true;
    }

    /**
     * Returns whether the method call is a call on a builder instance.
     * @param Node\Expr $call
     * @param Scope $scope
     * @return bool
     */
    protected function callIsQuery(Node\Expr $call, Scope $scope): bool
    {
        if ($call instanceof MethodCall) {
            $calledOn = $scope->getType($call->var);

            return $this->isBuilder($calledOn);
        } else if ($call instanceof Node\Expr\StaticCall) {
            /** @var Node\Name $class */
            $class = $call->class;
            if ($class instanceof Node\Name) {
                $modelClassName = $class->toCodeString();
                return is_subclass_of($modelClassName, Model::class);
            }
        }

        return false;
    }

    /**
     * Returns whether the method is one of the risky methods.
     * @param Identifier $name
     * @return bool
     */
    protected function isRiskyMethod(Identifier $name): bool
    {
        return in_array($name->toLowerString(), self::RISKY_METHODS, true);
    }

    /**
     * Returns whether the method might be a risky method depending on the parameters passed.
     * @param Identifier $name
     * @return bool
     */
    protected function isRiskyParamMethod(Identifier $name): bool
    {
        return in_array($name->toLowerString(), self::RISKY_PARAM_METHODS, true);
    }

    /**
     * Returns whether its argument is some builder instance.
     * @param Type $type
     * @return bool
     */
    protected function isBuilder(Type $type): bool
    {
        return !(new ObjectType(EloquentBuilder::class))->isSuperTypeOf($type)->no()
            || !(new ObjectType(QueryBuilder::class))->isSuperTypeOf($type)->no()
            || !(new ObjectType(Relation::class))->isSuperTypeOf($type)->no();
    }

    /**
     * Returns whether the Expr was not called on a Collection instance.
     * @param Node\Expr $expr
     * @param Scope $scope
     * @return bool
     */
    protected function isNotCalledOnCollection(Node\Expr $expr, Scope $scope): bool
    {
        $calledOnType = $scope->getType($expr);
        return !(new ObjectType(Collection::class))->isSuperTypeOf($calledOnType)->yes();
    }

    /**
     * Formats the error.
     * @param string $method_name
     * @return string
     */
    protected function formatError(string $method_name): string
    {
        return "Called '{$method_name}' on collection, but could have been retrieved as a query.";
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Larastan\Larastan\Properties\ModelPropertyExtension;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use stdClass;

use function array_diff;
use function array_map;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

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
 *
 * @implements Rule<MethodCall>
 */
class NoUnnecessaryCollectionCallRule implements Rule
{
    /**
     * The method names that can be applied on a Collection, but should be applied on a Builder.
     */
    protected const RISKY_METHODS = [
        'count',
        'isempty',
        'isnotempty',
        'last',
        'pop',
        'skip',
        'slice',
        'take',
        // Eloquent collection
        'diff',
        'except',
        'find',
        'modelkeys',
        'only',
    ];

    /**
     * The method names that can be applied on a Collection, but should in some cases be applied
     * on a builder depending on what parameter is passed to the method.
     */
    protected const RISKY_PARAM_METHODS = [
        'average',
        'avg',
        'firstwhere',
        'max',
        'min',
        'pluck',
        'sum',
        'where',
        'wherestrict',
    ];

    /** @var string[] The method names that should be checked by this rule - can be configured by the user. */
    protected array $shouldHandle;

    /**
     * @param  string[] $onlyMethods
     * @param  string[] $excludeMethods
     */
    public function __construct(
        protected ReflectionProvider $reflectionProvider,
        protected ModelPropertyExtension $propertyExtension,
        array $onlyMethods,
        array $excludeMethods,
    ) {
        $allMethods = array_merge(
            self::RISKY_METHODS,
            self::RISKY_PARAM_METHODS,
            [
                'first',
                'contains',
                'containsstrict',
            ],
        );

        if (! empty($onlyMethods)) {
            $this->shouldHandle = array_map(static function (string $methodName): string {
                return Str::lower($methodName);
            }, $onlyMethods);
        } else {
            $this->shouldHandle = $allMethods;
        }

        if (empty($excludeMethods)) {
            return;
        }

        $this->shouldHandle = array_diff($this->shouldHandle, array_map(static function (string $methodName): string {
            return Str::lower($methodName);
        }, $excludeMethods));
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Identifier) {
            return [];
        }

        $name = $node->name;

        if (! in_array($name->toLowerString(), $this->shouldHandle, true)) {
            return [];
        }

        if (! $this->isCalledOnCollection($node->var, $scope)) {
            // Method was not called on a collection, so no errors.
            return [];
        }

        $previousCall = $node->var;

        if (! $this->callIsQuery($previousCall, $scope)) {
            // Previous call wasn't on a Builder, so no errors.
            return [];
        }

        if (! ($previousCall->name instanceof Identifier)) {
            // Previous call was made dynamically e.g. User::query()->{$method}()
            // Can't really analyze it in this scenario so no errors.
            return [];
        }

        if ($name->toLowerString() === 'first') {
            if (count($node->args) === 0) {
                // 'first', also accepts a closure as an argument.
                return [$this->formatError($name->toString())];
            }
        } elseif ($this->isRiskyMethod($name)) {
            return [$this->formatError($name->toString())];
        } elseif ($this->isRiskyParamMethod($name)) {
            if (count($node->args) === 0) {
                // Calling e.g. DB::table()->pluck($columnName)->sum()
                // We have to check whether $columnName is actually a database column
                // and not an alias for some computed attribute
                if ($previousCall->name->name === 'pluck' && $this->firstArgIsDatabaseColumn($previousCall, $scope)) {
                    return [$this->formatError($name->toString())];
                }

                return [];
            }

            if ($this->firstArgIsDatabaseColumn($node, $scope)) {
                return [$this->formatError($name->toString())];
            }
        } elseif (in_array($name->toLowerString(), ['contains', 'containsstrict'], true)) {
            // 'contains' can also be called with Model instances or keys as its first argument
            /** @var Arg[] $args */
            $args = $node->args;
            if (count($args) === 1 && ! ($args[0]->value instanceof Node\FunctionLike)) {
                return [$this->formatError($name->toString())];
            }

            if ($this->firstArgIsDatabaseColumn($node, $scope)) {
                return [$this->formatError($name->toString())];
            }
        }

        return [];
    }

    /**
     * Determines whether the first argument is a string and references a database column.
     */
    protected function firstArgIsDatabaseColumn(Node\Expr\StaticCall|MethodCall $node, Scope $scope): bool
    {
        /** @var Arg[] $args */
        $args = $node->args;
        if (count($args) === 0 || ! ($args[0]->value instanceof Node\Scalar\String_)) {
            return false;
        }

        if ($node instanceof Node\Expr\StaticCall) {
            /** @var Node\Name $class */
            $class = $node->class;

            $modelReflection = $this->reflectionProvider->getClass($class->toCodeString());

            /** @var String_ $firstArg */
            $firstArg = $args[0]->value;

            return $this->propertyExtension->hasProperty($modelReflection, $firstArg->value);
        }

        $iterableType = $scope->getType($node->var)->getIterableValueType();

        if ((new ObjectType(stdClass::class))->isSuperTypeOf($iterableType)->yes()) {
            $previousCall = $node->var;
            if ($previousCall instanceof MethodCall) {
                $queryBuilderType = $scope->getType($previousCall->var);
                if ((new ObjectType(QueryBuilder::class))->isSuperTypeOf($queryBuilderType)->yes()) {
                    // We encountered a DB query such as DB::table(..)->get()->max('id')
                    // We assume max('id') could have been retrieved without calling get().
                    return true;
                }
            }

            return false;
        }

        if ((new ObjectType(Model::class))->isSuperTypeOf($iterableType)->yes()) {
            $iterableClassNames = $iterableType->getObjectClassNames();

            if (count($iterableClassNames) === 0) {
                return false;
            }

            $modelReflection = $this->reflectionProvider->getClass($iterableClassNames[0]);

            /** @var String_ $firstArg */
            $firstArg = $args[0]->value;

            return $this->propertyExtension->hasProperty($modelReflection, $firstArg->value);
        }

        return false;
    }

    /**
     * Returns whether the method call is a call on a builder instance.
     *
     * @phpstan-assert-if-true MethodCall|Node\Expr\StaticCall $call
     */
    protected function callIsQuery(Node\Expr $call, Scope $scope): bool
    {
        if ($call instanceof MethodCall) {
            $calledOn = $scope->getType($call->var);

            return $this->isBuilder($calledOn);
        }

        if ($call instanceof Node\Expr\StaticCall) {
            $class = $call->class;
            if ($class instanceof Node\Name) {
                $modelClassName = $class->toCodeString();

                return (new ObjectType(Model::class))->isSuperTypeOf(new ObjectType($modelClassName))->yes()
                    && $call->name instanceof Identifier
                    && in_array($call->name->toLowerString(), ['get', 'all', 'pluck'], true);
            }
        }

        return false;
    }

    /**
     * Returns whether the method is one of the risky methods.
     */
    protected function isRiskyMethod(Identifier $name): bool
    {
        return in_array($name->toLowerString(), self::RISKY_METHODS, true);
    }

    /**
     * Returns whether the method might be a risky method depending on the parameters passed.
     */
    protected function isRiskyParamMethod(Identifier $name): bool
    {
        return in_array($name->toLowerString(), self::RISKY_PARAM_METHODS, true);
    }

    /**
     * Returns whether its argument is some builder instance.
     */
    protected function isBuilder(Type $type): bool
    {
        return (new ObjectType(EloquentBuilder::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(QueryBuilder::class))->isSuperTypeOf($type)->yes()
            || (new ObjectType(Relation::class))->isSuperTypeOf($type)->yes();
    }

    /**
     * Returns whether the Expr was not called on a Collection instance.
     */
    protected function isCalledOnCollection(Node\Expr $expr, Scope $scope): bool
    {
        $calledOnType = $scope->getType($expr);

        return (new ObjectType(Collection::class))->isSuperTypeOf($calledOnType)->yes();
    }

    /**
     * Formats the error.
     */
    protected function formatError(string $methodName): RuleError
    {
        return RuleErrorBuilder::message(sprintf(
            "Called '%s' on Laravel collection, but could have been retrieved as a query.",
            $methodName,
        ))
            ->identifier('larastan.noUnnecessaryCollectionCall')
            ->build();
    }
}

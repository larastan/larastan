<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\ValidatedInput;
use Larastan\Larastan\Properties\ModelHelper;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BooleanType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionException;

use function array_slice;
use function array_values;
use function count;
use function in_array;
use function sprintf;
use function strrchr;
use function strtolower;
use function substr;

/**
 * On a JSON request, input(), validated() and the methods like them return the
 * decoded JSON value, which can be an integer. MySQL compares a string column with
 * an integer numerically, so `where('token', 0)` matches every token that does not
 * start with a digit. validated() is only as strict as the rules, so it counts too.
 *
 * @implements Rule<Expr\CallLike>
 */
class NoMixedRequestInputInQueryRule implements Rule
{
    /** Methods that take a value in any argument after the column. */
    private const WHERE_METHODS = [
        'where',
        'orwhere',
        'wherenot',
        'orwherenot',
        'firstwhere',
        'wherein',
        'orwherein',
        'wherenotin',
        'orwherenotin',
    ];

    /** Methods that take a primary key as the first argument. */
    private const KEY_METHODS = [
        'find',
        'findorfail',
        'findornew',
        'findor',
        'findmany',
        'findsole',
        'wherekey',
        'wherekeynot',
    ];

    /** Request methods that return the decoded JSON value on a JSON request. */
    private const REQUEST_INPUT_METHODS = ['input', 'get', 'post', 'json', 'validated'];

    public function getNodeType(): string
    {
        return Expr\CallLike::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ((! $node instanceof MethodCall && ! $node instanceof StaticCall) || ! $node->name instanceof Identifier) {
            return [];
        }

        $methodName = strtolower($node->name->toString());
        $args       = $node->getArgs();

        if (! in_array($methodName, self::WHERE_METHODS, true) && ! in_array($methodName, self::KEY_METHODS, true)) {
            return [];
        }

        $receiverType = $this->receiverType($node, $scope);

        if ($receiverType === null) {
            return [];
        }

        $model = $this->model($receiverType);

        if (in_array($methodName, self::WHERE_METHODS, true)) {
            $values = $this->whereValues($args);
        } else {
            $values = isset($args[0]) ? [[$this->keyName($model), $args[0]->value]] : [];
        }

        $errors = [];

        foreach ($values as [$column, $value]) {
            if (! $this->isRequestInput($value, $scope)) {
                continue;
            }

            $columnDescription = $this->describeColumn($model, $column, $scope);

            if ($columnDescription === null) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Mixed request input is compared with %s in %s(); a JSON body can send an integer, which MySQL compares with a string numerically.',
                $columnDescription,
                $node->name->toString(),
            ))
                ->tip('Cast it first, for example with $request->string(\'key\')->toString(). Validated input keeps the type the client sent.')
                ->identifier('larastan.noMixedRequestInputInQuery')
                ->line($value->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function receiverType(MethodCall|StaticCall $node, Scope $scope): Type|null
    {
        if ($node instanceof StaticCall) {
            $type = $node->class instanceof Name
                ? $scope->resolveTypeByName($node->class)
                : $scope->getType($node->class)->getObjectTypeOrClassStringObjectType();
        } else {
            $type = $scope->getType($node->var);
        }

        $queryType = TypeCombinator::union(
            new ObjectType(Model::class),
            new ObjectType(EloquentBuilder::class),
            new ObjectType(QueryBuilder::class),
            new ObjectType(Relation::class),
        );

        return $queryType->isSuperTypeOf($type)->yes() ? $type : null;
    }

    /**
     * The model whose columns the query compares against, or null when it is not known.
     * A receiver can be a union such as `User|Builder<User>`, which ide-helper writes
     * for `User::query()`.
     */
    private function model(Type $receiverType): ClassReflection|null
    {
        $models = [];

        foreach ($receiverType->getObjectClassReflections() as $reflection) {
            $modelType = match (true) {
                $reflection->is(EloquentBuilder::class) => $reflection->getActiveTemplateTypeMap()->getType('TModel'),
                $reflection->is(Relation::class) => $reflection->getActiveTemplateTypeMap()->getType('TRelatedModel'),
                default => new ObjectType($reflection->getName()),
            };

            foreach ($modelType?->getObjectClassReflections() ?? [] as $model) {
                $models[$model->getName()] = $model;
            }
        }

        if (count($models) !== 1) {
            return null;
        }

        $model = array_values($models)[0];

        return $model->is(Model::class) && ! $model->isAbstract() ? $model : null;
    }

    private function keyName(ClassReflection|null $model): string|null
    {
        if ($model === null) {
            return null;
        }

        try {
            return ModelHelper::newInstanceWithoutConstructor($model)->getKeyName();
        } catch (ReflectionException) {
            return null;
        }
    }

    private function isRequestInput(Expr $expr, Scope $scope): bool
    {
        if ($expr instanceof ArrayDimFetch) {
            return $this->isRequestInputContainer($expr->var, $scope);
        }

        if ($expr instanceof FuncCall) {
            return $expr->name instanceof Name
                && strtolower($expr->name->toString()) === 'request'
                && $expr->getArgs() !== [];
        }

        if ($expr instanceof StaticCall) {
            return $expr->class instanceof Name
                && $expr->name instanceof Identifier
                && in_array(strtolower($expr->name->toString()), self::REQUEST_INPUT_METHODS, true)
                && $expr->getArgs() !== []
                && (new ObjectType(RequestFacade::class))->isSuperTypeOf($scope->resolveTypeByName($expr->class))->yes();
        }

        if ($expr instanceof MethodCall) {
            if (! $expr->name instanceof Identifier || $expr->getArgs() === []) {
                return false;
            }

            $methodName = strtolower($expr->name->toString());
            $type       = $scope->getType($expr->var);

            return (in_array($methodName, self::REQUEST_INPUT_METHODS, true) && (new ObjectType(Request::class))->isSuperTypeOf($type)->yes())
                || ($methodName === 'input' && (new ObjectType(ValidatedInput::class))->isSuperTypeOf($type)->yes());
        }

        if ($expr instanceof PropertyFetch && $expr->name instanceof Identifier) {
            $type = $scope->getType($expr->var);

            if (
                ! (new ObjectType(Request::class))->isSuperTypeOf($type)->yes()
                && ! (new ObjectType(ValidatedInput::class))->isSuperTypeOf($type)->yes()
            ) {
                return false;
            }

            foreach ($type->getObjectClassReflections() as $classReflection) {
                if ($classReflection->hasNativeProperty($expr->name->toString())) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /** `$request->all()`, `$request->validated()` or `$request->safe()`, read with `['key']`. */
    private function isRequestInputContainer(Expr $expr, Scope $scope): bool
    {
        if (! $expr instanceof MethodCall || ! $expr->name instanceof Identifier || $expr->getArgs() !== []) {
            return false;
        }

        return in_array(strtolower($expr->name->toString()), ['all', 'input', 'validated', 'safe'], true)
            && (new ObjectType(Request::class))->isSuperTypeOf($scope->getType($expr->var))->yes();
    }

    /**
     * Describes the column for the error, or returns null for an integer, float or
     * boolean column: a JSON number compared with it gives nothing a string would not.
     */
    private function describeColumn(ClassReflection|null $model, string|null $column, Scope $scope): string|null
    {
        if ($column === null) {
            return 'a column of unknown type';
        }

        $qualifier = strrchr($column, '.');
        $property  = $qualifier === false ? $column : substr($qualifier, 1);

        if ($model === null || ! $model->hasProperty($property)) {
            return sprintf('column %s of unknown type', $column);
        }

        $type = TypeCombinator::removeNull($model->getProperty($property, $scope)->getReadableType());

        if (TypeCombinator::union(new IntegerType(), new FloatType(), new BooleanType())->isSuperTypeOf($type)->yes()) {
            return null;
        }

        return $type->isString()->yes()
            ? sprintf('string column %s', $column)
            : sprintf('column %s', $column);
    }

    /**
     * @param array<Arg> $args
     *
     * @return list<array{string|null, Expr}>
     */
    private function whereValues(array $args): array
    {
        if (! isset($args[0])) {
            return [];
        }

        $column = $args[0]->value;
        $values = [];

        if ($column instanceof Array_) {
            foreach ($column->items as $item) {
                $values[] = [$item->key instanceof String_ ? $item->key->value : null, $item->value];
            }
        }

        foreach (array_slice($args, 1) as $arg) {
            $values[] = [$column instanceof String_ ? $column->value : null, $arg->value];
        }

        return $values;
    }
}

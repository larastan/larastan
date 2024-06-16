<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PhpParser;
use PhpParser\NodeFinder;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\ObjectType;
use ReflectionException;

use function array_key_exists;
use function array_merge;
use function class_basename;
use function count;
use function is_string;
use function property_exists;
use function strtolower;

/** @see https://github.com/psalm/laravel-psalm-plugin/blob/master/src/SchemaAggregator.php */
final class SchemaAggregator
{
    /** @param array<string, SchemaTable> $tables */
    public function __construct(private ReflectionProvider $reflectionProvider, public array $tables = [])
    {
    }

    /** @param  array<int, PhpParser\Node\Stmt> $stmts */
    public function addStatements(array $stmts): void
    {
        $nodeFinder = new NodeFinder();

        /** @var PhpParser\Node\Stmt\Class_[] $classes */
        $classes = $nodeFinder->findInstanceOf($stmts, PhpParser\Node\Stmt\Class_::class);

        foreach ($classes as $stmt) {
            $this->addClassStatements($stmt->stmts);
        }
    }

    /** @param  array<int, PhpParser\Node\Stmt> $stmts */
    private function addClassStatements(array $stmts): void
    {
        foreach ($stmts as $stmt) {
            if (
                ! ($stmt instanceof PhpParser\Node\Stmt\ClassMethod)
                || $stmt->name->name === 'down'
                || ! $stmt->stmts
            ) {
                continue;
            }

            $this->addUpMethodStatements($stmt->stmts);
        }
    }

    /** @param  PhpParser\Node\Stmt[] $stmts */
    private function addUpMethodStatements(array $stmts): void
    {
        $nodeFinder = new NodeFinder();
        $methods    = $nodeFinder->findInstanceOf($stmts, PhpParser\Node\Stmt\Expression::class);

        foreach ($methods as $stmt) {
            if (
                $stmt instanceof PhpParser\Node\Stmt\Expression
                && $stmt->expr instanceof PhpParser\Node\Expr\MethodCall
                && $stmt->expr->var instanceof PhpParser\Node\Expr\StaticCall
                && $stmt->expr->var->class instanceof PhpParser\Node\Name
                && $stmt->expr->var->name instanceof PhpParser\Node\Identifier
                && ($stmt->expr->var->name->toString() === 'connection' || $stmt->expr->var->name->toString() === 'setConnection')
                && ($stmt->expr->var->class->toCodeString() === '\Schema' || (new ObjectType('Illuminate\Support\Facades\Schema'))->isSuperTypeOf(new ObjectType($stmt->expr->var->class->toCodeString()))->yes())
            ) {
                $statement = $stmt->expr;
            } elseif (
                $stmt instanceof PhpParser\Node\Stmt\Expression
                && $stmt->expr instanceof PhpParser\Node\Expr\StaticCall
                && $stmt->expr->class instanceof PhpParser\Node\Name
                && $stmt->expr->name instanceof PhpParser\Node\Identifier
                && ($stmt->expr->class->toCodeString() === '\Schema' || (new ObjectType('Illuminate\Support\Facades\Schema'))->isSuperTypeOf(new ObjectType($stmt->expr->class->toCodeString()))->yes())
            ) {
                $statement = $stmt->expr;
            } else {
                continue;
            }

            if (! $statement->name instanceof PhpParser\Node\Identifier) {
                continue;
            }

            switch ($statement->name->name) {
                case 'create':
                    $this->alterTable($statement, true);
                    break;

                case 'table':
                    $this->alterTable($statement, false);
                    break;

                case 'drop':
                case 'dropIfExists':
                    $this->dropTable($statement);
                    break;

                case 'rename':
                    $this->renameTableThroughStaticCall($statement);
            }
        }
    }

    private function alterTable(PhpParser\Node\Expr\StaticCall|PhpParser\Node\Expr\MethodCall $call, bool $creating): void
    {
        if (
            ! isset($call->args[0])
            || ! $call->getArgs()[0]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            return;
        }

        $tableName = $call->getArgs()[0]->value->value;

        if ($creating) {
            $this->tables[$tableName] = new SchemaTable($tableName);
        }

        if (
            ! isset($call->args[1])
            || ! $call->getArgs()[1]->value instanceof PhpParser\Node\Expr\Closure
            || count($call->getArgs()[1]->value->params) < 1
            || ($call->getArgs()[1]->value->params[0]->type instanceof PhpParser\Node\Name
                && ! (new ObjectType('Illuminate\Database\Schema\Blueprint'))->isSuperTypeOf(new ObjectType($call->getArgs()[1]->value->params[0]->type->toCodeString()))->yes()
            )
        ) {
            return;
        }

        $updateClosure = $call->getArgs()[1]->value;

        if (
            ! ($call->getArgs()[1]->value->params[0]->var instanceof PhpParser\Node\Expr\Variable)
            || ! is_string($call->getArgs()[1]->value->params[0]->var->name)
        ) {
            return;
        }

        $argName = $call->getArgs()[1]->value->params[0]->var->name;

        $this->processColumnUpdates($tableName, $argName, $this->getUpdateStatements($updateClosure));
    }

    /**
     * @param  PhpParser\Node\Stmt[] $stmts
     *
     * @throws Exception
     */
    private function processColumnUpdates(string $tableName, string $argName, array $stmts): void
    {
        if (! isset($this->tables[$tableName])) {
            return;
        }

        $table = $this->tables[$tableName];

        foreach ($stmts as $stmt) {
            if (
                ! ($stmt instanceof PhpParser\Node\Stmt\Expression)
                || ! ($stmt->expr instanceof PhpParser\Node\Expr\MethodCall)
                || ! ($stmt->expr->name instanceof PhpParser\Node\Identifier)
            ) {
                continue;
            }

            $rootVar = $stmt->expr;

            $firstMethodCall = $rootVar;

            $nullable = false;

            while ($rootVar instanceof PhpParser\Node\Expr\MethodCall) {
                if (
                    $rootVar->name instanceof PhpParser\Node\Identifier
                    && $rootVar->name->name === 'nullable'
                    && $this->getNullableArgumentValue($rootVar) === true
                ) {
                    $nullable = true;
                }

                $firstMethodCall = $rootVar;
                $rootVar         = $rootVar->var;
            }

            if (
                ! ($rootVar instanceof PhpParser\Node\Expr\Variable)
                || $rootVar->name !== $argName
                || ! ($firstMethodCall->name instanceof PhpParser\Node\Identifier)
            ) {
                continue;
            }

            $firstArg  = $firstMethodCall->getArgs()[0]->value ?? null;
            $secondArg = $firstMethodCall->getArgs()[1]->value ?? null;

            if ($firstMethodCall->name->name === 'foreignIdFor') {
                if (
                    $firstArg instanceof PhpParser\Node\Expr\ClassConstFetch
                    && $firstArg->class instanceof PhpParser\Node\Name
                ) {
                    $modelClass = $firstArg->class->toCodeString();
                } elseif ($firstArg instanceof PhpParser\Node\Scalar\String_) {
                    $modelClass = $firstArg->value;
                } else {
                    continue;
                }

                $columnName = Str::snake(class_basename($modelClass)) . '_id';
                if ($secondArg instanceof PhpParser\Node\Scalar\String_) {
                    $columnName = $secondArg->value;
                }

                $type = $this->getModelReferenceType($modelClass);
                $table->setColumn(new SchemaColumn($columnName, $type ?? 'int', $nullable));

                continue;
            }

            if (! $firstArg instanceof PhpParser\Node\Scalar\String_) {
                if ($firstArg instanceof PhpParser\Node\Expr\Array_ && $firstMethodCall->name->name === 'dropColumn') {
                    foreach ($firstArg->items as $arrayItem) {
                        if ($arrayItem === null || ! ($arrayItem->value instanceof PhpParser\Node\Scalar\String_)) {
                            continue;
                        }

                        $table->dropColumn($arrayItem->value->value);
                    }
                }

                if (
                    $firstMethodCall->name->name === 'timestamps'
                    || $firstMethodCall->name->name === 'timestampsTz'
                    || $firstMethodCall->name->name === 'nullableTimestamps'
                    || $firstMethodCall->name->name === 'nullableTimestampsTz'
                    || $firstMethodCall->name->name === 'rememberToken'
                ) {
                    switch (strtolower($firstMethodCall->name->name)) {
                        case 'droptimestamps':
                        case 'droptimestampstz':
                            $table->dropColumn('created_at');
                            $table->dropColumn('updated_at');
                            break;

                        case 'remembertoken':
                            $table->setColumn(new SchemaColumn('remember_token', 'string', $nullable));
                            break;

                        case 'dropremembertoken':
                            $table->dropColumn('remember_token');
                            break;

                        case 'timestamps':
                        case 'timestampstz':
                        case 'nullabletimestamps':
                            $table->setColumn(new SchemaColumn('created_at', 'string', true));
                            $table->setColumn(new SchemaColumn('updated_at', 'string', true));
                            break;
                    }

                    continue;
                }

                $defaultsMap = [
                    'softDeletes' => 'deleted_at',
                    'softDeletesTz' => 'deleted_at',
                    'softDeletesDatetime' => 'deleted_at',
                    'dropSoftDeletes' => 'deleted_at',
                    'dropSoftDeletesTz' => 'deleted_at',
                    'uuid' => 'uuid',
                    'id' => 'id',
                    'ulid' => 'ulid',
                    'ipAddress' => 'ip_address',
                    'macAddress' => 'mac_address',
                ];
                if (! array_key_exists($firstMethodCall->name->name, $defaultsMap)) {
                    continue;
                }

                $columnName = $defaultsMap[$firstMethodCall->name->name];
            } else {
                $columnName = $firstArg->value;
            }

            $secondArgArray = null;

            if ($secondArg instanceof PhpParser\Node\Expr\Array_) {
                $secondArgArray = [];

                foreach ($secondArg->items as $arrayItem) {
                    if ($arrayItem === null || ! ($arrayItem->value instanceof PhpParser\Node\Scalar\String_)) {
                        continue;
                    }

                    $secondArgArray[] = $arrayItem->value->value;
                }
            }

            $this->processStatementAlterMethod(
                strtolower($firstMethodCall->name->name),
                $firstMethodCall,
                $table,
                $columnName,
                $nullable,
                $secondArg,
                $argName,
                $tableName,
                $secondArgArray,
                $stmt,
            );
        }
    }

    private function dropTable(PhpParser\Node\Expr\StaticCall|PhpParser\Node\Expr\MethodCall $call): void
    {
        if (
            ! isset($call->args[0])
            || ! $call->getArgs()[0]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            return;
        }

        $tableName = $call->getArgs()[0]->value->value;

        unset($this->tables[$tableName]);
    }

    private function renameTableThroughStaticCall(PhpParser\Node\Expr\StaticCall|PhpParser\Node\Expr\MethodCall $call): void
    {
        if (
            ! isset($call->args[0], $call->args[1])
            || ! $call->getArgs()[0]->value instanceof PhpParser\Node\Scalar\String_
            || ! $call->getArgs()[1]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            return;
        }

        $oldTableName = $call->getArgs()[0]->value->value;
        $newTableName = $call->getArgs()[1]->value->value;

        $this->renameTable($oldTableName, $newTableName);
    }

    private function renameTableThroughMethodCall(SchemaTable $oldTable, PhpParser\Node\Expr\MethodCall $call): void
    {
        if (
            ! isset($call->args[0])
            || ! $call->getArgs()[0]->value instanceof PhpParser\Node\Scalar\String_
        ) {
            return;
        }

        /** @var PhpParser\Node\Scalar\String_ $methodCallArgument */
        $methodCallArgument = $call->getArgs()[0]->value;

        $oldTableName = $oldTable->name;
        $newTableName = $methodCallArgument->value;

        $this->renameTable($oldTableName, $newTableName);
    }

    private function renameTable(string $oldTableName, string $newTableName): void
    {
        if (! isset($this->tables[$oldTableName])) {
            return;
        }

        $table = $this->tables[$oldTableName];

        unset($this->tables[$oldTableName]);

        $table->name = $newTableName;

        $this->tables[$newTableName] = $table;
    }

    private function getModelReferenceType(string $modelClass): string|null
    {
        $classReflection = $this->reflectionProvider->getClass($modelClass);
        try {
            /** @var Model $modelInstance */
            $modelInstance = $classReflection->getNativeReflection()->newInstanceWithoutConstructor();
        } catch (ReflectionException) {
            return null;
        }

        $tableName = $modelInstance->getTable();

        if (! array_key_exists($tableName, $this->tables)) {
            return null;
        }

        $table  = $this->tables[$tableName];
        $column = $modelInstance->getKeyName();

        if (! array_key_exists($column, $table->columns)) {
            return null;
        }

        return $table->columns[$column]->readableType;
    }

    private function getNullableArgumentValue(PhpParser\Node\Expr\MethodCall $rootVar): bool
    {
        if (! array_key_exists(0, $rootVar->args)) {
            return true;
        }

        $arg = $rootVar->args[0];

        if (! ($arg instanceof PhpParser\Node\Arg)) {
            return true;
        }

        $argExpression = $arg->value;

        if (! ($argExpression instanceof PhpParser\Node\Expr\ConstFetch)) {
            return true;
        }

        return $argExpression->name->getFirst() === 'true';
    }

    /** @return PhpParser\Node\Stmt\Expression[] */
    private function getUpdateStatements(PhpParser\Node\Expr $updateClosure): array
    {
        if (! property_exists($updateClosure, 'stmts')) {
            return [];
        }

        $statements = [];
        $nodeFinder = new NodeFinder();

        foreach ($updateClosure->stmts as $updateStatement) {
            if ($updateStatement instanceof PhpParser\Node\Stmt\If_) {
                $statements = array_merge(
                    $statements,
                    $nodeFinder->findInstanceOf($updateStatement, PhpParser\Node\Stmt\Expression::class),
                );

                continue;
            }

            $statements[] = $updateStatement;
        }

        return $statements;
    }

    /**
     * @param array<int, mixed> $secondArgArray
     *
     * @throws Exception
     */
    private function processStatementAlterMethod(
        string $method,
        PhpParser\Node\Expr\MethodCall|null $firstMethodCall,
        SchemaTable $table,
        string $columnName,
        bool $nullable,
        mixed $secondArg,
        PhpParser\Node\Expr|string $argName,
        string $tableName,
        array|null $secondArgArray,
        PhpParser\Node\Stmt\Expression $stmt,
    ): void {
        switch ($method) {
            case 'addcolumn':
                $this->processStatementAlterMethod(
                    strtolower($firstMethodCall->args[0]->value->value ?? ''),
                    null,
                    $table,
                    $firstMethodCall->args[1]->value->value ?? '',
                    $nullable,
                    $secondArg,
                    $argName,
                    $tableName,
                    $secondArgArray,
                    $stmt,
                );

                return;

            case 'biginteger':
            case 'increments':
            case 'id':
            case 'integer':
            case 'integerincrements':
            case 'mediumincrements':
            case 'mediuminteger':
            case 'smallincrements':
            case 'smallinteger':
            case 'tinyincrements':
            case 'tinyinteger':
            case 'unsignedbiginteger':
            case 'unsignedinteger':
            case 'unsignedmediuminteger':
            case 'unsignedsmallinteger':
            case 'unsignedtinyinteger':
            case 'bigincrements':
            case 'foreignid':
                $table->setColumn(new SchemaColumn($columnName, 'int', $nullable));

                return;

            case 'char':
            case 'datetimetz':
            case 'date':
            case 'datetime':
            case 'ipaddress':
            case 'json':
            case 'jsonb':
            case 'linestring':
            case 'longtext':
            case 'macaddress':
            case 'mediumtext':
            case 'multilinestring':
            case 'string':
            case 'text':
            case 'time':
            case 'timestamp':
            case 'ulid':
            case 'uuid':
            case 'binary':
                $table->setColumn(new SchemaColumn($columnName, 'string', $nullable));

                return;

            case 'boolean':
                $table->setColumn(new SchemaColumn($columnName, 'bool', $nullable));

                return;

            case 'geometry':
            case 'geometrycollection':
            case 'multipoint':
            case 'multipolygon':
            case 'multipolygonz':
            case 'point':
            case 'polygon':
            case 'computed':
                $table->setColumn(new SchemaColumn($columnName, 'mixed', $nullable));

                return;

            case 'double':
            case 'float':
            case 'unsigneddecimal':
            case 'decimal':
                $table->setColumn(new SchemaColumn($columnName, 'float', $nullable));

                return;

            case 'after':
                if (
                    $secondArg instanceof PhpParser\Node\Expr\Closure
                    && $secondArg->params[0]->var instanceof PhpParser\Node\Expr\Variable
                    && ! ($secondArg->params[0]->var->name instanceof PhpParser\Node\Expr)
                ) {
                    $argName = $secondArg->params[0]->var->name;
                    $this->processColumnUpdates($tableName, $argName, $secondArg->stmts);
                }

                return;

            case 'dropcolumn':
            case 'dropifexists':
            case 'dropsoftdeletes':
            case 'dropsoftdeletestz':
            case 'removecolumn':
            case 'drop':
                $table->dropColumn($columnName);

                return;

            case 'dropforeign':
            case 'dropindex':
            case 'dropprimary':
            case 'dropunique':
            case 'foreign':
            case 'index':
            case 'primary':
            case 'renameindex':
            case 'spatialIndex':
            case 'unique':
            case 'dropspatialindex':
                return;

            case 'dropmorphs':
                $table->dropColumn($columnName . '_type');
                $table->dropColumn($columnName . '_id');

                return;

            case 'enum':
                $table->setColumn(new SchemaColumn($columnName, 'enum', $nullable, $secondArgArray));

                return;

            case 'morphs':
                $table->setColumn(new SchemaColumn($columnName . '_type', 'string', $nullable));
                $table->setColumn(new SchemaColumn($columnName . '_id', 'int', $nullable));

                return;

            case 'nullablemorphs':
                $table->setColumn(new SchemaColumn($columnName . '_type', 'string', true));
                $table->setColumn(new SchemaColumn($columnName . '_id', 'int', true));

                return;

            case 'nullableuuidmorphs':
                $table->setColumn(new SchemaColumn($columnName . '_type', 'string', true));
                $table->setColumn(new SchemaColumn($columnName . '_id', 'string', true));

                return;

            case 'rename':
                /** @var PhpParser\Node\Expr\MethodCall $methodCall */
                $methodCall = $stmt->expr;
                $this->renameTableThroughMethodCall($table, $methodCall);

                return;

            case 'renamecolumn':
                if ($secondArg instanceof PhpParser\Node\Scalar\String_) {
                    $table->renameColumn($columnName, $secondArg->value);
                }

                return;

            case 'set':
                $table->setColumn(new SchemaColumn($columnName, 'set', $nullable, $secondArgArray));

                return;

            case 'softdeletestz':
            case 'timestamptz':
            case 'timetz':
            case 'year':
            case 'softdeletes':
                $table->setColumn(new SchemaColumn($columnName, 'string', true));

                return;

            case 'uuidmorphs':
                $table->setColumn(new SchemaColumn($columnName . '_type', 'string', $nullable));
                $table->setColumn(new SchemaColumn($columnName . '_id', 'string', $nullable));

                return;

            default:
                // We know a property exists with a name, we just don't know its type.
                $table->setColumn(new SchemaColumn($columnName, 'mixed', $nullable));
        }
    }
}

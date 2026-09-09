<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Type\ObjectType;

use function array_merge;
use function in_array;
use function preg_match;
use function str_starts_with;
use function strtolower;

/** @implements Rule<Node\Expr\CallLike> */
class RelationExistenceRule implements Rule
{
    public function __construct(private RelationExistenceHelper $relationExistenceHelper)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        if ((! $node instanceof MethodCall && ! $node instanceof Node\Expr\StaticCall) || ! $node->name instanceof Node\Identifier || $node->isFirstClassCallable()) {
            return [];
        }

        $method    = strtolower($node->name->name);
        $aggregate = preg_match('/^(with|load)(aggregate|count|max|min|sum|avg|exists)$/', $method) === 1;

        if (
            ! $aggregate && ! in_array($method, [
                'has',
                'orhas',
                'doesnthave',
                'ordoesnthave',
                'wherehas',
                'withwherehas',
                'orwherehas',
                'wheredoesnthave',
                'orwheredoesnthave',
                'whererelation',
                'orwhererelation',
                'withwhererelation',
                'wheredoesnthaverelation',
                'orwheredoesnthaverelation',
                'hasmorph',
                'orhasmorph',
                'doesnthavemorph',
                'ordoesnthavemorph',
                'wherehasmorph',
                'orwherehasmorph',
                'wheredoesnthavemorph',
                'orwheredoesnthavemorph',
                'wheremorphrelation',
                'orwheremorphrelation',
                'wheremorphdoesnthaverelation',
                'orwheremorphdoesnthaverelation',
                'with',
                'withonly',
                'load',
                'loadmissing',
            ], true)
        ) {
            return [];
        }

        $args = $node->getArgs();

        if ($args === [] || $args[0]->unpack) {
            return [];
        }

        foreach ($args as $arg) {
            if ($arg->name !== null && in_array($arg->name->toString(), ['relation', 'relations'], true)) {
                $args = [$arg];
                break;
            }
        }

        $receiver = $node instanceof Node\Expr\StaticCall ? $node->class : $node->var;
        $type     = $receiver instanceof Node\Name ? $scope->resolveTypeByName($receiver) : $scope->getType($receiver);

        $collection = (new ObjectType(Collection::class))->isSuperTypeOf($type)->yes();

        if ($collection && str_starts_with($method, 'load')) {
            $type = $type->getTemplateType(Collection::class, 'TModel');
        } elseif ($collection) {
            return [];
        }

        $variadic = in_array($method, ['with', 'load', 'loadmissing', 'withcount'], true)
            || ($method === 'loadcount' && ! $collection && (new ObjectType(Model::class))->isSuperTypeOf($type)->yes());
        $args     = $variadic && $scope->getType($args[0]->value)->isString()->yes() ? $args : [$args[0]];
        $errors   = [];

        foreach ($args as $arg) {
            if ($arg->unpack) {
                continue;
            }

            $errors = array_merge($errors, $this->relationExistenceHelper->check($scope->getType($arg->value), $type, $node, $scope, $aggregate));
        }

        return $errors;
    }
}

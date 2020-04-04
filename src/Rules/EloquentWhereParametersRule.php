<?php declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;

class EloquentWhereParametersRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        if (!$node->name instanceof Identifier) {
            return $errors;
        }

        /**
         * @var \PhpParser\Node\Identifier $name
         */
        $name = $node->name;

        if ($name->toLowerString() !== 'where') {
            return $errors;
        }

        $calledOnType = $scope->getType($node->var);
        $eloquentBuilderClass = Builder::class;
        if (!(new ObjectType($eloquentBuilderClass))->isSuperTypeOf($calledOnType)->yes()) {
            return $errors;
        }

        /** @var ClassReflection $modelReflection */
        $modelReflection = $scope->getClassReflection($node->getType());
        $modelClassName = $modelReflection->getName();

        /** @var \PhpParser\Node\Arg[] $args */
        $args = $node->args;

        if ($args[0]->value instanceof Node\Expr\Closure) {
            // dont bother trying to verify these for now
            return $errors;
        }

        if ($args[0]->value instanceof Node\Scalar\String_) {
            // @todo handle analyzing strings
            echo "// @todo handle analyzing strings";
            return $errors;
        }

        if ($args[0]->value instanceof Node\Expr\Array_) {
            foreach($args[0]->value->items as $item) {
                /** @var \PhpParser\Node\Expr\ArrayItem $item */
                $columnName = $item->key->value;
                $columnValue = $item->value->value;

                if (!$modelReflection->hasProperty($columnName)) {
                    $errors[] = "cannot find property $columnName on $modelClassName";
                }

                // @todo: ensure value is proper type
            }
        }

        return $errors;
    }
}

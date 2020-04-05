<?php declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

class EloquentWhereParametersRule implements Rule
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        // @todo: is there a way to invoke on static and method calls?
        //return MethodCall::class;
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        /** @var \PhpParser\Node\Expr\StaticCall $node */
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

        $calledOnType = $scope->getType($node);
        $eloquentBuilderClass = Builder::class;

        if (!(new ObjectType($eloquentBuilderClass))->isSuperTypeOf($calledOnType)->yes()) {
            return $errors;
        }

        /** @var ClassReflection $modelReflection */
        $modelClassName = $node->class->toCodeString();
        $modelReflection = $this->reflectionProvider->getClass($modelClassName);

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
                    continue;
                }

                $propertyValueType = $modelReflection->getProperty($columnName, $scope)->getWritableType();
                $valueType = $scope->getType($item->value);

                // @todo: strict types? not sure if that's an option somewhere or what we should assume
                if ($propertyValueType->accepts($valueType, true)->no()) {
                    $errors[] = "property $columnName of $modelClassName expects value of type {$propertyValueType->describe(VerbosityLevel::getRecommendedLevelByType($propertyValueType))}, but got {$valueType->describe(VerbosityLevel::getRecommendedLevelByType($valueType))} $columnValue";
                    continue;
                }
                // @todo: is there a way to check if the property is fillable?
            }
        }

        return $errors;
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/** @implements Rule<Node\Expr\CallLike> */
class RelationExistenceRule implements Rule
{
    public function __construct(private ModelRuleHelper $modelRuleHelper)
    {
    }

    /**
     * @inheritDoc
     */
    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    /**
     * @inheritDoc
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof MethodCall && ! $node instanceof Node\Expr\StaticCall) {
            return [];
        }

        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if (! in_array($node->name->name, [
            'has',
            'with',
            'orHas',
            'doesntHave',
            'orDoesntHave',
            'whereHas',
            'orWhereHas',
            'whereDoesntHave',
            'orWhereDoesntHave',
        ],
            true)
        ) {
            return [];
        }

        $args = $node->getArgs();

        if (count($args) < 1) {
            return [];
        }

        $valueType = $scope->getType($args[0]->value);

        /** @var ConstantStringType[] $relations */
        $relations = [];

        if ($valueType->isConstantArray()->yes()) {
            $arrays = $valueType->getConstantArrays();

            foreach ($arrays as $array) {
                $relations = array_merge(
                    $relations,
                    ...array_map(function (Type $type) {
                        return $type->getConstantStrings();
                    }, $array->getKeyTypes()),
                    ...array_map(function (Type $type) {
                        return $type->getConstantStrings();
                    }, $array->getValueTypes()),
                );
            }
        } else {
            $constants = $valueType->getConstantStrings();

            if ($constants === []) {
                return [];
            }

            $relations = $constants;
        }

        $errors = [];

        foreach ($relations as $relationType) {
            $relationName = explode(':', $relationType->getValue())[0];

            $calledOnNode = $node instanceof MethodCall ? $node->var : $node->class;

            if ($calledOnNode instanceof Node\Name) {
                $calledOnType = new ObjectType($calledOnNode->toString());
            } else {
                $calledOnType = $scope->getType($calledOnNode);
            }

            $closure = function (Type $calledOnType, string $relationName, Node $node) use ($scope): array {
                $modelReflection = $this->modelRuleHelper->findModelReflectionFromType($calledOnType);

                if ($modelReflection === null) {
                    return [];
                }

                if (! $modelReflection->hasMethod($relationName)) {
                    return [
                        $this->getRuleError($relationName, $modelReflection, $node),
                    ];
                }

                $relationMethod = $modelReflection->getMethod($relationName, $scope);

                if (! (new ObjectType(Relation::class))->isSuperTypeOf(ParametersAcceptorSelector::selectSingle($relationMethod->getVariants())->getReturnType())->yes()) {
                    return [
                        $this->getRuleError($relationName, $modelReflection, $node),
                    ];
                }

                return [];
            };

            if (str_contains($relationName, '.')) {
                // Nested relations
                $relations = explode('.', $relationName);

                foreach ($relations as $relation) {
                    $result = $closure($calledOnType, $relation, $node);

                    if ($result !== []) {
                        return $result;
                    }

                    $modelReflection = $this->modelRuleHelper->findModelReflectionFromType($calledOnType);

                    if ($modelReflection === null) {
                        return [];
                    }

                    // Dynamic method return type extensions are not taken into account here.
                    // So we simulate a method call to the relation here to get the return type.
                    $calledOnType = $scope->getType(new MethodCall(new Node\Expr\New_(new Node\Name($modelReflection->getName())), new Node\Identifier($relation)));
                }

                return [];
            }

            $errors = array_merge($errors, $closure($calledOnType, $relationName, $node));
        }

        return $errors;
    }

    private function getRuleError(
        string $relationName,
        \PHPStan\Reflection\ClassReflection $modelReflection,
        Node $node
    ): \PHPStan\Rules\RuleError {
        return RuleErrorBuilder::message(sprintf("Relation '%s' is not found in %s model.", $relationName,
            $modelReflection->getName()))
            ->identifier('rules.relationExistence')
            ->line($node->getAttribute('startLine'))
            ->build();
    }
}

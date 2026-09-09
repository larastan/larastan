<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Relations\Relation;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function sprintf;
use function strtolower;

final class RelationExistenceHelper
{
    public function __construct(private ModelRuleHelper $modelRuleHelper)
    {
    }

    /** @return RuleError[] */
    public function check(Type $relations, Type $modelType, Node $node, Scope $scope, bool $aggregate = false): array
    {
        $errors = [];

        foreach (array_unique($this->relationNames($relations)) as $name) {
            $name = explode(':', $name)[0];

            if ($aggregate) {
                $alias = explode(' ', $name);

                if (count($alias) === 3 && strtolower($alias[1]) === 'as') {
                    $name = $alias[0];
                }
            }

            $calledOnType = $modelType;

            // Aggregates call the relation method directly; they do not traverse dotted paths.
            foreach ($aggregate ? [$name] : explode('.', $name) as $relationName) {
                $modelReflection = $this->modelRuleHelper->findModelReflectionFromType($calledOnType);

                if ($modelReflection === null) {
                    break;
                }

                if (
                    ! $modelReflection->hasMethod($relationName)
                    || ! (new ObjectType(Relation::class))->isSuperTypeOf(
                        ParametersAcceptorSelector::selectFromArgs($scope, [], $modelReflection->getMethod($relationName, $scope)->getVariants())->getReturnType(),
                    )->yes()
                ) {
                    $errors[$modelReflection->getName() . '::' . $relationName] = RuleErrorBuilder::message(sprintf(
                        "Relation '%s' is not found in %s model.",
                        $relationName,
                        $modelReflection->getName(),
                    ))->identifier('larastan.relationExistence')->line($node->getStartLine())->build();

                    break;
                }

                // Simulate a call so dynamic return type extensions can resolve the related model.
                $calledOnType = $scope->getType(new MethodCall(new Node\Expr\New_(new Node\Name\FullyQualified($modelReflection->getName())), $relationName));
            }
        }

        return array_values($errors);
    }

    /** @return string[] */
    private function relationNames(Type $type, string $prefix = ''): array
    {
        $names = [];

        foreach ($type->getConstantStrings() as $name) {
            $names[] = $prefix . $name->getValue();
        }

        foreach ($type->getConstantArrays() as $array) {
            foreach ($array->getKeyTypes() as $index => $key) {
                $value = $array->getValueTypes()[$index];

                if ($key->isString()->yes()) {
                    $name    = $prefix . $key->getValue();
                    $names[] = $name;

                    if ($value->isArray()->yes()) {
                        $names = array_merge($names, $this->relationNames($value, explode(':', $name)[0] . '.'));
                    }
                } else {
                    foreach ($value->getConstantStrings() as $name) {
                        $names[] = $prefix . $name->getValue();
                    }
                }
            }
        }

        return $names;
    }
}

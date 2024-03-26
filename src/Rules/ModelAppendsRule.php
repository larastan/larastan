<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Properties\ModelPropertyHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function array_reduce;
use function sprintf;

/**
 * This rule validates that properties in the $appends array
 * both exist in the model and are computed properties.
 *
 * Accessors (attributes that modify the value of a database field)
 * **are not** considered computed properties and should not
 * be in the $appends or they will always be null.
 *
 * @implements Rule<Property>
 */
class ModelAppendsRule implements Rule
{
    public function __construct(
        private ModelPropertyHelper $modelPropertyHelper,
    ) {
    }

    public function getNodeType(): string
    {
        return Property::class;
    }

    /** @return array<int, RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->props[0]->name->toString() !== 'appends') {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if (! $classReflection?->isSubclassOf(Model::class)) {
            return [];
        }

        $value = $node->props[0]->default;

        if (! $value instanceof Array_) {
            return [];
        }

        $appends = $value->items;

        return array_reduce($appends, function ($errors, $appended) use ($classReflection, $scope) {
            if (! $appended?->value instanceof String_) {
                return $errors;
            }

            $name = $appended->value->value;

            $hasDatebaseProperty = $this->modelPropertyHelper->hasDatebaseProperty($classReflection, $name);
            $hasAccessor         = $this->modelPropertyHelper->hasAccessor($classReflection, $name, strictGenerics: false);

            if ($hasDatebaseProperty) {
                $errors[] = RuleErrorBuilder::message(sprintf("Property '%s' is not a computed property, remove from \$appends.", $name))
                    ->identifier('rules.modelAppends')
                    ->line($appended->getLine())
                    ->file($scope->getFile())
                    ->build();
            }

            if (! $hasDatebaseProperty && ! $hasAccessor) {
                $errors[] = RuleErrorBuilder::message(sprintf("Property '%s' does not exist in model.", $name))
                    ->identifier('rules.modelAppends')
                    ->line($appended->getLine())
                    ->file($scope->getFile())
                    ->build();
            }

            return $errors;
        }, []);
    }
}

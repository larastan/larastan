<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\Validation\RuleParser;
use Larastan\Larastan\Support\Validation\RuleTypes;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_filter;
use function array_keys;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function str_contains;
use function str_starts_with;

/**
 * Reports pairs of rules that contradict each other on fields whose rules hold nothing conditional.
 *
 * @implements Rule<Return_>
 */
final class FormRequestValidationRulePairsRule implements Rule
{
    /** Rules without a value type that still never change what `required` does. */
    private const UNCONDITIONAL = ['Bail', 'Required', 'Nullable', 'Missing'];

    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /** @inheritDoc */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();

        if (
            $node->expr === null
            || $class === null
            || ! $class->isSubclassOfClass($this->reflectionProvider->getClass(FormRequest::class))
            || $scope->getFunctionName() !== 'rules'
            || $scope->isInAnonymousFunction()
        ) {
            return [];
        }

        [$fields] = (new RuleParser())->fields($node->expr, $scope);
        $names    = array_keys($fields);
        $errors   = [];

        // Wildcards may address any field.
        foreach (str_contains(implode('.', $names), '*') ? [] : $fields as $field => $readings) {
            $rules = $readings[0]->rules ?? [];

            if (
                $readings === null
                || count($readings) !== 1
                || $readings[0]->types !== []
                || str_contains($field, '.')
                || array_filter($names, static fn (string $name): bool => str_starts_with($name, $field . '.')) !== []
                || ! isset($rules['Required'])
            ) {
                continue;
            }

            foreach (array_keys($rules) as $rule) {
                if (! in_array($rule, self::UNCONDITIONAL, true) && RuleTypes::valueType($rule) === null) {
                    continue 2;
                }
            }

            if (isset($rules['Missing'])) {
                $errors[] = RuleErrorBuilder::message(sprintf("Field '%s' has conflicting validation rules 'required' and 'missing'.", $field))
                    ->identifier('larastan.formRequest.requiredMissing')
                    ->tip("The 'required' and 'missing' rules require the field to be both present and absent. Choose whether the field must be present or absent.")
                    ->build();
            } elseif (isset($rules['Nullable'])) {
                $errors[] = RuleErrorBuilder::message(sprintf("Field '%s' has a 'nullable' rule but does not accept null.", $field))
                    ->identifier('larastan.formRequest.requiredNullable')
                    ->tip("The 'required' rule rejects null even when 'nullable' is present. Decide whether null should be valid for this field.")
                    ->build();
            }
        }

        return $errors;
    }
}

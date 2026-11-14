<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Larastan\Larastan\Support\Validation\RuleParser;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function array_filter;
use function array_keys;
use function count;
use function implode;
use function method_exists;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_starts_with;

/**
 * Reports pairs of rules that contradict each other on fields whose rules hold nothing conditional.
 *
 * @implements Rule<Return_>
 */
#[Conditional(attribute: new AutowiredService(), if: '%checkFormRequestTypes%')]
final class FormRequestValidationRulePairsRule implements Rule
{
    /** Built-in rules under which a field is validated, required or absent only some of the time. */
    private const CONDITIONAL = '/^(Sometimes|Exclude|(Accepted|Declined|Missing|Present|Prohibited|Required)(If|Unless|With))/';

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
            || $scope->getFunctionName() !== 'rules'
            || $scope->isInAnonymousFunction()
            || $class === null
            || ! $class->isSubclassOfClass($this->reflectionProvider->getClass(FormRequest::class))
        ) {
            return [];
        }

        [$fields] = RuleParser::fields($node->expr, $scope);
        $names    = array_keys($fields);
        $errors   = [];

        // Wildcards may address any field.
        foreach (str_contains(implode('.', $names), '*') ? [] : $fields as $field => $readings) {
            $rules = $readings[0]->rules ?? [];

            if (
                $readings === null
                || count($readings) !== 1
                || $readings[0]->types !== []
                || ! isset($rules['Required'])
                || str_contains($field, '.')
                || array_filter($names, static fn (string $name): bool => str_starts_with($name, $field . '.')) !== []
            ) {
                continue;
            }

            foreach (array_keys($rules) as $rule) {
                // Rule objects Larastan does not read are kept under their class name, which no built-in rule has.
                if (! method_exists(ValidatesAttributes::class, 'validate' . $rule) || preg_match(self::CONDITIONAL, $rule) === 1) {
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

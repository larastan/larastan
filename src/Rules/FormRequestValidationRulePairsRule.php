<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Validation\Validator;
use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function count;
use function explode;
use function in_array;
use function sprintf;
use function str_contains;
use function str_starts_with;
use function strtolower;

/** @implements Rule<Return_> */
final class FormRequestValidationRulePairsRule implements Rule
{
    public function __construct(private ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return Return_::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $scope->getFunction();

        if (
            $node->expr === null
            || $scope->isInAnonymousFunction()
            || ! $method instanceof MethodReflection
            || strtolower($method->getName()) !== 'rules'
            || $scope->getClassReflection()?->is(FormRequest::class) !== true
        ) {
            return [];
        }

        $type   = $scope->getType($node->expr);
        $arrays = $type->getConstantArrays();

        if (! $type->isConstantArray()->yes() || count($arrays) !== 1 || $arrays[0]->isUnsealed()->yes()) {
            return [];
        }

        $array  = $arrays[0];
        $fields = [];

        foreach ($array->getKeyTypes() as $index => $key) {
            $field = $this->constantString($key);

            if ($array->isOptionalKey($index)) {
                return [];
            }

            if ($field === null) {
                continue;
            }

            if (str_contains($field, '*')) {
                return [];
            }

            $fields[$index] = $field;
        }

        $errors = [];

        foreach ($fields as $index => $field) {
            if ($field === '' || str_contains($field, '.') || str_contains($field, '\\')) {
                continue;
            }

            foreach ($fields as $other) {
                if (str_starts_with($other, $field . '.')) {
                    continue 2;
                }
            }

            $rules = $this->ruleNames($array->getValueTypes()[$index]);

            if ($rules === null || ! in_array('Required', $rules, true)) {
                continue;
            }

            $name = $array->getKeyTypes()[$index]->describe(VerbosityLevel::precise());

            if (in_array('Missing', $rules, true)) {
                $errors[] = RuleErrorBuilder::message(sprintf("Field %s has conflicting validation rules 'required' and 'missing'.", $name))
                    ->identifier('larastan.formRequest.requiredMissing')
                    ->tip("The 'required' and 'missing' rules require the field to be both present and absent. Choose whether the field must be present or absent.")
                    ->line($node->getStartLine())
                    ->build();
            } elseif (in_array('Nullable', $rules, true)) {
                $errors[] = RuleErrorBuilder::message(sprintf("Field %s has a 'nullable' rule but does not accept null.", $name))
                    ->identifier('larastan.formRequest.requiredNullable')
                    ->tip("The 'required' rule rejects null even when 'nullable' is present. Decide whether null should be valid for this field.")
                    ->line($node->getStartLine())
                    ->build();
            }
        }

        return $errors;
    }

    /** @return list<string>|null */
    private function ruleNames(Type $type): array|null
    {
        $string = $this->constantString($type);

        if ($string !== null) {
            $tokens = explode('|', $string);
        } else {
            $arrays = $type->getConstantArrays();

            if (! $type->isConstantArray()->yes() || count($arrays) !== 1 || $arrays[0]->isUnsealed()->yes()) {
                return null;
            }

            $tokens = [];

            foreach ($arrays[0]->getValueTypes() as $index => $value) {
                $token = $this->constantString($value);

                if ($token === null || $arrays[0]->isOptionalKey($index)) {
                    return null;
                }

                $tokens[] = $token;
            }
        }

        $validator = $this->reflectionProvider->getClass(Validator::class);
        $names     = [];

        foreach ($tokens as $token) {
            [$name] = ValidationRuleParser::parse($token);

            if (! $validator->hasNativeMethod('validate' . $name) || strtolower($name) === 'sometimes' || str_starts_with(strtolower($name), 'exclude')) {
                return null;
            }

            // ponytail: skip conditional families instead of evaluating applicability.
            foreach (['Required', 'Missing', 'Present', 'Accepted', 'Declined', 'Prohibited'] as $family) {
                if ($name !== $family && str_starts_with($name, $family)) {
                    return null;
                }
            }

            $names[] = $name;
        }

        return $names;
    }

    private function constantString(Type $type): string|null
    {
        $strings = $type->getConstantStrings();

        return count($strings) === 1 && $type->equals($strings[0]) ? $strings[0]->getValue() : null;
    }
}

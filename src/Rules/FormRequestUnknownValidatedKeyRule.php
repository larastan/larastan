<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Support\ValidatedInput;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

use function array_intersect;
use function array_map;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function in_array;
use function sprintf;
use function strtolower;

/** @implements Rule<MethodCall> */
final class FormRequestUnknownValidatedKeyRule implements Rule
{
    public function __construct(private FormRequestHelper $formRequestHelper, private bool $checkUnionTypes)
    {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier || $node->isFirstClassCallable()) {
            return [];
        }

        $name = strtolower($node->name->toString());

        if (! in_array($name, ['validated', 'safe', 'only'], true)) {
            return [];
        }

        $args = $this->arguments($node, $scope);

        if ($args === null || $args === []) {
            return [];
        }

        $request    = $node->var;
        $methodName = $name;

        if ($name === 'only') {
            $method = $scope->getMethodReflection($scope->getType($node->var), 'only');

            if (
                $method?->getDeclaringClass()->getName() !== ValidatedInput::class
                || (! $request instanceof MethodCall && ! $request instanceof NullsafeMethodCall)
                || ! $request->name instanceof Node\Identifier
                || strtolower($request->name->toString()) !== 'safe'
            ) {
                return [];
            }

            $safeArgs = $this->arguments($request, $scope);

            if ($safeArgs === null || ($safeArgs !== [] && ! $scope->getType($safeArgs[0]->value)->isNull()->yes())) {
                return [];
            }

            $request    = $request->var;
            $methodName = 'safe';
        }

        $keys     = $this->selectors($args, $name, $scope);
        $receiver = TypeCombinator::removeNull($scope->getType($request));

        if ($keys === null || $receiver instanceof BenevolentUnionType || $receiver instanceof NeverType) {
            return [];
        }

        // ponytail: keep request arms separate; optional shape entries are not invalid union arms.
        $members = $receiver instanceof UnionType ? $receiver->getTypes() : [$receiver];
        $shapes  = array_map(fn (Type $member): Type|null => $this->formRequestHelper->getValidatedDataType($member, $methodName, $scope), $members);
        $errors  = [];
        $seen    = [];

        foreach ($keys as $key) {
            $display = $key->describe(VerbosityLevel::precise());

            if (isset($seen[$display])) {
                continue;
            }

            $seen[$display] = true;
            $strings        = $key->getConstantStrings();

            if ($strings !== []) {
                $parts = explode('.', $strings[0]->getValue());

                if (array_intersect($parts, ['*', '{first}', '{last}', '\\*', '\\{first}', '\\{last}']) !== []) {
                    continue;
                }

                $segments = array_map(static fn (string $part): Type => new ConstantStringType($part), $parts);
            } else {
                $segments = [$key];
            }

            $invalid = [];

            foreach ($shapes as $index => $shape) {
                if ($shape === null || ! $this->pathIsAbsent($shape, $segments)) {
                    continue;
                }

                $invalid[] = TypeCombinator::union(...array_map(
                    static fn (string $class): Type => new ObjectType($class),
                    $members[$index]->getObjectClassNames(),
                ));
            }

            if ($invalid === [] || (! $this->checkUnionTypes && count($invalid) !== count($members))) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Key %s does not exist in validated data of %s.',
                $display,
                TypeCombinator::union(...$invalid)->describe(VerbosityLevel::typeOnly()),
            ))
                ->identifier('larastan.formRequest.unknownValidatedKey')
                ->tip((count($invalid) === count($members) ? '' : 'Other possible request types may allow this key. ') . "Check the key against the fields included by the request's validation rules.")
                ->line($node->getStartLine())
                ->build();
        }

        return $errors;
    }

    /** @return list<Arg>|null */
    private function arguments(MethodCall|NullsafeMethodCall $call, Scope $scope): array|null
    {
        if ($call->isFirstClassCallable() || ! $call->name instanceof Node\Identifier) {
            return null;
        }

        $method = $scope->getMethodReflection($scope->getType($call->var), $call->name->toString());

        if ($method === null) {
            return null;
        }

        $args       = $call->getArgs();
        $variant    = ParametersAcceptorSelector::selectFromArgs($scope, $args, $method->getVariants(), $method->getNamedArgumentsVariants());
        $parameters = array_map(static fn ($parameter): string => $parameter->getName(), $variant->getParameters());

        foreach ($args as $arg) {
            if ($arg->unpack || ($arg->name !== null && ! in_array($arg->name->toString(), $parameters, true))) {
                return null;
            }
        }

        return ArgumentsNormalizer::reorderArgs($variant, $args);
    }

    /**
     * @param non-empty-list<Arg> $args
     *
     * @return list<Type>|null
     */
    private function selectors(array $args, string $method, Scope $scope): array|null
    {
        $type = $scope->getType($args[0]->value);

        if ($method === 'validated') {
            $constants = $type->getConstantScalarTypes();

            return count($constants) === 1 && $type->equals($constants[0]) && ($type->isString()->yes() || $type->isInteger()->yes())
                ? [$type]
                : null;
        }

        if ($method === 'only' && $type->isString()->yes()) {
            $keys = array_map(static fn (Arg $arg): Type => $scope->getType($arg->value), $args);
        } else {
            $arrays = $type->getConstantArrays();

            if (count($args) !== 1 || ! $type->isConstantArray()->yes() || count($arrays) !== 1 || $arrays[0]->isUnsealed()->yes() || $arrays[0]->getOptionalKeys() !== []) {
                return null;
            }

            $keys = $arrays[0]->getValueTypes();
        }

        foreach ($keys as $key) {
            $strings = $key->getConstantStrings();

            if (count($strings) !== 1 || ! $key->equals($strings[0])) {
                return null;
            }
        }

        return array_values($keys);
    }

    /** @param list<Type> $segments */
    private function pathIsAbsent(Type $type, array $segments): bool
    {
        if ($segments === [] || $type instanceof NeverType || $type instanceof BenevolentUnionType) {
            return false;
        }

        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $member) {
                if (! $this->pathIsAbsent($member, $segments)) {
                    return false;
                }
            }

            return true;
        }

        if (! $type->isArray()->yes()) {
            return $type->isScalar()->yes() || $type->isNull()->yes();
        }

        $segment = array_shift($segments);
        $hasKey  = $type->hasOffsetValueType($segment);

        if ($hasKey->no()) {
            $arrays = $type->getConstantArrays();

            return $type->isConstantArray()->yes() && count($arrays) === 1 && ! $arrays[0]->isUnsealed()->yes();
        }

        return $this->pathIsAbsent($type->getOffsetValueType($segment), $segments);
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Support\Arr;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\MixedType;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

use function in_array;

/** @implements Rule<StaticCall> */
class ArrGetPullRule implements Rule
{
    public function __construct(
        private ReflectionProvider $reflectionProvider,
    ) {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if (! $node->class instanceof Node\Name) {
            return [];
        }

        $methodName = strtolower($node->name->name);

        if (! in_array($methodName, ['get', 'pull'], true)) {
            return [];
        }

        if (count($node->getArgs()) < 2) {
            return [];
        }

        $classReflection = $this->reflectionProvider->getClass($scope->resolveName($node->class));

        if (!$classReflection->is(Arr::class)) {
            return [];
        }

        [$array, $key] = $node->getArgs();

        $keyType = $scope->getType($key->value);

        if ($keyType instanceof MixedType) {
            return [];
        }

        $arrayType = $scope->getType($array->value);

        if ($arrayType instanceof MixedType) {
            return [];
        }

        if ($arrayType->getIterableKeyType()->isSuperTypeOf($keyType)->yes()) {
            return [];
        }

        $keyString = $keyType->describe(VerbosityLevel::precise());
        $arrayString = $arrayType->describe(VerbosityLevel::precise());

        return [
            RuleErrorBuilder::message("Key {$keyString} does not exist in {$arrayString}.")
                ->identifier('rules.arrGetPullInvalidKey')
                ->build()
        ];
    }
}

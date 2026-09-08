<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Http\FormRequest;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function in_array;
use function sprintf;
use function strtolower;

/** @implements Rule<MethodCall> */
final class FormRequestPrematureValidatedAccessRule implements Rule
{
    private const EARLY_HOOKS = [
        'prepareforvalidation',
        'authorize',
        'rules',
        'validationdata',
        'messages',
        'attributes',
        'withvalidator',
        'after',
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            ! $node->name instanceof Node\Identifier
            || ! $node->var instanceof Variable
            || $node->var->name !== 'this'
            || $node->isFirstClassCallable()
            || $scope->isInAnonymousFunction()
        ) {
            return [];
        }

        $name  = strtolower($node->name->toString());
        $hook  = $scope->getFunction();
        $class = $scope->getClassReflection();

        if (
            ! in_array($name, ['validated', 'safe'], true)
            || ! $hook instanceof MethodReflection
            || ! in_array(strtolower($hook->getName()), self::EARLY_HOOKS, true)
            || $class?->is(FormRequest::class) !== true
        ) {
            return [];
        }

        $method = $scope->getMethodReflection($scope->getType($node->var), $name);

        if ($method === null || $method->getDeclaringClass()->getName() !== FormRequest::class) {
            return [];
        }

        // ponytail: lexical guidance only; custom initialization uses normal identifier suppression.
        return [
            RuleErrorBuilder::message(sprintf(
                'Method %s::%s() should not be called in %s().',
                $class->getDisplayName(),
                $name,
                $hook->getName(),
            ))
            ->identifier('larastan.formRequest.prematureValidatedAccess')
            ->tip('Laravel normally initializes the request validator later. Use input() for unvalidated request data, or move work requiring validated data to passedValidation() or the controller.')
            ->line($node->getStartLine())
            ->build(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

use function in_array;
use function sprintf;

/**
 * Reports validated data being read in the methods Laravel calls before the validator of a form request exists.
 *
 * @implements Rule<MethodCall>
 */
final class FormRequestPrematureValidatedAccessRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @inheritDoc */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $scope->getClassReflection();
        $hook  = $scope->getFunctionName();

        if (
            ! $node->var instanceof Variable
            || $node->var->name !== 'this'
            || ! $node->name instanceof Identifier
            || $node->isFirstClassCallable()
            || $class === null
            || ! $class->is(FormRequest::class)
            || ! in_array($hook, FormRequestHelper::BEFORE_VALIDATION, true)
            || $scope->isInAnonymousFunction()
        ) {
            return [];
        }

        $method = $class->hasNativeMethod($node->name->name) ? $class->getNativeMethod($node->name->name) : null;

        if ($method === null || ! in_array($method->getName(), ['validated', 'safe'], true) || $method->getDeclaringClass()->getName() !== FormRequest::class) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf('Method %s::%s() should not be called in %s().', $class->getDisplayName(), $method->getName(), $hook))
                ->identifier('larastan.formRequest.prematureValidatedAccess')
                ->tip('Laravel normally initializes the request validator later. Use input() for unvalidated request data, or move work requiring validated data to passedValidation() or the controller.')
                ->build(),
        ];
    }
}

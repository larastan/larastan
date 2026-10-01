<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Larastan\Larastan\Support\FormRequestHelper;
use Larastan\Larastan\Support\Validation\ValidatedData;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;

use function count;
use function sprintf;
use function strtolower;

/**
 * Reports keys read from the validated data of a form request that its rules never put there.
 *
 * @implements Rule<MethodCall>
 */
final class FormRequestUnknownValidatedKeyRule implements Rule
{
    public function __construct(private FormRequestHelper $helper, private bool $checkUnionTypes)
    {
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /** @inheritDoc */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = self::methodName($node);

        // Only a result of safe() that is used on the spot still tells which request it came from.
        $call = $method === 'only' && ($node->var instanceof MethodCall || $node->var instanceof NullsafeMethodCall) ? $node->var : $node;

        if ($method !== 'validated' && self::methodName($call) !== 'safe') {
            return [];
        }

        $arguments = [];

        foreach ($node->getArgs() as $position => $argument) {
            if ($argument->unpack) {
                return [];
            }

            if ($method !== 'only' && ($argument->name === null ? $position !== 0 : $argument->name->name === 'default')) {
                continue;
            }

            $arguments[] = $scope->getType($argument->value);
        }

        $paths = [];

        foreach (($method === 'validated' ? $arguments : ValidatedData::keys($arguments)) ?? [] as $key) {
            $segments = ValidatedData::segments($key);

            if ($segments === null) {
                return [];
            }

            $paths[$key->describe(VerbosityLevel::precise())] = $segments;
        }

        $requestType = TypeCombinator::removeNull($scope->getType($call->var));
        $requests    = $requestType instanceof BenevolentUnionType ? [] : $this->helper->requests($requestType);
        $errors      = [];

        foreach ($paths as $key => $segments) {
            $missing = [];

            foreach ($requests as $request) {
                $shapes = $this->helper->inherits($request, $method === 'validated' ? $method : 'safe') ? $this->helper->shapes($request) : null;

                if ($shapes === null || ! ValidatedData::lookup($shapes[0], $segments)[1]->no()) {
                    continue;
                }

                $missing[] = new ObjectType($request->getName());
            }

            $partial = count($missing) < count($requests);

            if ($missing === [] || ($partial && ! $this->checkUnionTypes)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Key %s does not exist in validated data of %s.',
                $key,
                TypeCombinator::union(...$missing)->describe(VerbosityLevel::typeOnly()),
            ))
                ->identifier('larastan.formRequest.unknownValidatedKey')
                ->tip(($partial ? 'Other possible request types may allow this key. ' : '') . "Check the key against the fields included by the request's validation rules.")
                ->build();
        }

        return $errors;
    }

    private static function methodName(Node $node): string|null
    {
        return ($node instanceof MethodCall || $node instanceof NullsafeMethodCall) && $node->name instanceof Identifier && ! $node->isFirstClassCallable()
            ? strtolower($node->name->name)
            : null;
    }
}

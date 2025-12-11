<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;

use function in_array;
use function sprintf;

/** @implements Rule<StaticCall> */
class NoAuthFacadeInRequestScopeRule implements Rule
{
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param StaticCall $node
     *
     * @return list<IdentifierRuleError>
     *
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->class instanceof Name) {
            return [];
        }

        $className = $node->class->toString();

        if ($className !== Auth::class && ! (new ObjectType(Auth::class))->isSuperTypeOf(new ObjectType($className))->yes()) {
            return [];
        }

        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        if (! in_array($methodName, ['check', 'user', 'guest'], true)) {
            return [];
        }

        $message = match ($methodName) {
            'check' => 'Do not use Auth::check() in a class that has access to the request. Use $%s->user() !== null instead.',
            'user' => 'Do not use Auth::user() in a class that has access to the request. Use $%s->user() instead.',
            'guest' => 'Do not use Auth::guest() in a class that has access to the request. Use $%s->user() === null instead.',
        };

        if ($scope->isInClass() && $scope->getClassReflection()->is(Request::class)) {
            return [
                RuleErrorBuilder::message(sprintf($message, 'this'))
                    ->identifier('larastan.noAuthFacadeInRequestScope')
                    ->fixNode($node, static function (Node $node) use ($methodName) {
                        $variable = new Node\Expr\Variable('this');

                        return match ($methodName) {
                            'check' => new NotIdentical(new MethodCall($variable, 'user', []), new Node\Expr\ConstFetch(new Name('null'))),
                            'user' => new MethodCall($variable, 'user', []),
                            'guest' => new Identical(new MethodCall($variable, 'user', []), new Node\Expr\ConstFetch(new Name('null'))),
                        };
                    })
                    ->build(),
            ];
        }

        if (! $scope->hasVariableType('request')->yes()) {
            return [];
        }

        $requestType = $scope->getVariableType('request');

        if (! (new ObjectType(Request::class))->isSuperTypeOf($requestType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf($message, 'request'))
                ->identifier('larastan.noAuthFacadeInRequestScope')
                ->fixNode($node, static function (Node $node) use ($methodName) {
                    $variable = new Node\Expr\Variable('request');

                    return match ($methodName) {
                        'check' => new NotIdentical(new MethodCall($variable, 'user', []), new Node\Expr\ConstFetch(new Name('null'))),
                        'user' => new MethodCall($variable, 'user', []),
                        'guest' => new Identical(new MethodCall($variable, 'user', []), new Node\Expr\ConstFetch(new Name('null'))),
                    };
                })
                ->build(),
        ];
    }
}

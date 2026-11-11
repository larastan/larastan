<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\ViewName;
use Larastan\Larastan\Support\CallArgumentsHelper;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\ObjectType;

/** @implements Collector<Node\Expr\CallLike, string> */
#[AutowiredService]
final class UsedEmailSendViewCollector implements Collector
{
    public function __construct(private CallArgumentsHelper $callArgumentsHelper)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    /** @param Node\Expr\CallLike $node */
    public function processNode(Node $node, Scope $scope): string|null
    {
        if (
            ! $node instanceof Node\Expr\StaticCall
            && ! $node instanceof Node\Expr\MethodCall
        ) {
            return null;
        }

        $name = $node->name;

        if (! $name instanceof Node\Identifier || $name->name !== 'send') {
            return null;
        }

        if ($node instanceof Node\Expr\StaticCall) {
            $class = $node->class;

            if (! $class instanceof Node\Name) {
                return null;
            }

            $class = $scope->resolveName($class);

            if (! (new ObjectType(Mail::class))->isSuperTypeOf(new ObjectType($class))->yes()) {
                return null;
            }
        } else {
            $type = $scope->getType($node->var);

            if (! (new ObjectType(MailerContract::class))->isSuperTypeOf($type)->yes()) {
                return null;
            }
        }

        $view = $this->callArgumentsHelper->ofMethodCall($node, $scope)[0] ?? null;

        return $view !== null && ! $view->unpack && $view->value instanceof Node\Scalar\String_
            ? ViewName::normalize($view->value->value)
            : null;
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Collectors;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\View\ViewName;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Type\ObjectType;

use function count;
use function in_array;

/** @implements Collector<Node\Expr\MethodCall, string> */
final class UsedEmailViewCollector implements Collector
{
    private const VIEW_METHOD_NAMES = ['view', 'html', 'text', 'markdown'];

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    /** @param Node\Expr\MethodCall $node */
    public function processNode(Node $node, Scope $scope): string|null
    {
        $name = $node->name;

        if (! $name instanceof Identifier) {
            return null;
        }

        if (! in_array($name->name, self::VIEW_METHOD_NAMES, true)) {
            return null;
        }

        if (count($node->getArgs()) === 0) {
            return null;
        }

        $class = $node->var;

        $type = $scope->getType($class);

        $isContent     = (new ObjectType(Content::class))->isSuperTypeOf($type)->yes();
        $isMailable    = (new ObjectType(Mailable::class))->isSuperTypeOf($type)->yes();
        $isMailMessage = (new ObjectType(MailMessage::class))->isSuperTypeOf($type)->yes();

        if (! $isContent && ! $isMailable && ! $isMailMessage) {
            return null;
        }

        $template = $node->getArgs()[0]->value;

        if (! $template instanceof Node\Scalar\String_) {
            return null;
        }

        return ViewName::normalize($template->value);
    }
}

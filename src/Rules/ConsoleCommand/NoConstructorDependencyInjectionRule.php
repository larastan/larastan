<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\ConsoleCommand;

use Illuminate\Console\Command;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function count;
use function sprintf;

/** @implements Rule<InClassMethodNode> */
final class NoConstructorDependencyInjectionRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    /**
     * @param InClassMethodNode $node
     *
     * @return RuleError[] errors
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $method = $node->getMethodReflection();

        if (! $method->isConstructor()) {
            return [];
        }

        $classReflection = $node->getClassReflection();

        if (! $classReflection->is(Command::class)) {
            return [];
        }

        if ($classReflection->isAbstract()) {
            return [];
        }

        $methodNode = $node->getOriginalNode();

        if (count($methodNode->params) > 0) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Console command "%s" should not have constructor arguments.',
                        $classReflection->getName(),
                    ),
                )->line($methodNode->getLine())
                    ->tip('Move all dependencies to the handle() or __invoke() methods.')
                    ->identifier('larastan.consoleConstructorInjection')
                    ->build(),
            ];
        }

        return [];
    }
}

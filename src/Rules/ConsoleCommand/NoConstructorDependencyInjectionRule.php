<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\ConsoleCommand;

use Illuminate\Console\Command;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function count;
use function sprintf;

/** @implements Rule<InClassNode> */
final class NoConstructorDependencyInjectionRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return list<RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (! $classReflection->isSubclassOf(Command::class)) {
            return [];
        }

        if ($classReflection->isAbstract()) {
            return [];
        }

        $originalNode = $node->getOriginalNode();
        if (! $originalNode instanceof Class_) {
            return [];
        }

        $constructor = null;
        foreach ($originalNode->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\ClassMethod && $stmt->name->toString() === '__construct') {
                $constructor = $stmt;
                break;
            }
        }

        if ($constructor === null) {
            return [];
        }

        $params = $constructor->params;

        if (count($params) > 0) {
            return [
                RuleErrorBuilder::message(
                    sprintf(
                        'Console command "%s" should not have constructor arguments. Use dependency injection in the handle() method instead.',
                        $classReflection->getName(),
                    ),
                )->line($constructor->getLine())
                    ->tip('Move all dependencies to the handle() method parameters for better testability and Laravel best practices.')
                    ->identifier('larastan.console.constructorInjection')
                    ->build(),
            ];
        }

        return [];
    }
}

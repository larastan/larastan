<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Support\Facades\Config;
use Larastan\Larastan\Internal\ConfigHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\TypeCombinator;

use function sprintf;

/** @implements Rule<StaticCall> */
final class ConfigCollectionRule implements Rule
{
    public function __construct(private ConfigHelper $configHelper)
    {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param StaticCall              $node
     * @param Scope&DependencyTracker $scope
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Identifier || $node->name->name !== 'collection') {
            return [];
        }

        if (! $node->class instanceof Name || $node->class->toString() !== Config::class) {
            return [];
        }

        if (! $this->configHelper->hasConfigPaths()) {
            return [];
        }

        $args = $node->getArgs();

        if ($args === []) {
            return [];
        }

        $returnTypes = $this->configHelper->getReturnTypes($args, $scope);
        $returnType  = TypeCombinator::union(...$returnTypes);

        if (! $returnType->isArray()->no()) {
            return [];
        }

        $constantStrings = $scope->getType($args[0]->value)->getConstantStrings();

        if ($constantStrings === []) {
            return [];
        }

        $key = $constantStrings[0]->getValue();

        return [
            RuleErrorBuilder::message(sprintf('Config key \'%s\' is not an array.', $key))
                ->identifier('larastan.configCollection')
                ->build(),
        ];
    }
}

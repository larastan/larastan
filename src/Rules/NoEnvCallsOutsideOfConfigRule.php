<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Larastan\Larastan\Concerns\HasContainer;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;

use function config_path;
use function str_starts_with;

/**
 * Catches `env()` calls outside of the config directory.
 *
 * @implements Rule<FuncCall>
 */
class NoEnvCallsOutsideOfConfigRule implements Rule
{
    use HasContainer;

    public function getNodeType(): string
    {
        return FuncCall::class;
    }

    /** @return array<int, RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->name;

        if (! $name instanceof Name) {
            return [];
        }

        if ($scope->resolveName($name) !== 'env') {
            return [];
        }

        if (! $this->isCalledOutsideOfConfig($node, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Called 'env' outside of the config directory which returns null when the config is cached, use 'config'.")
                ->identifier('larastan.noEnvCallsOutsideOfConfig')
                ->line($node->getLine())
                ->file($scope->getFile())
                ->build(),
        ];
    }

    protected function isCalledOutsideOfConfig(FuncCall $call, Scope $scope): bool
    {
        return str_starts_with($scope->getFile(), config_path()) === false;
    }
}

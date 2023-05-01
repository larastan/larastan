<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Catches inefficient instantiation of models using Model::make().
 *
 * For example:
 * User::make()
 *
 * It is functionally equivalent to simply use the constructor:
 * new User()
 *
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\MethodCall>
 */
class NoModelMakeRule implements Rule
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    protected $reflectionProvider;

    /**
     * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /**
     * @param \PhpParser\Node $node
     * @param \PHPStan\Analyser\Scope $scope
     * @return array<int, \PHPStan\Rules\RuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        /** @var \PhpParser\Node\Expr\StaticCall $node due to @see getNodeType() */
        $name = $node->name;
        if (! $name instanceof Identifier) {
            return [];
        }

        if ($name->name !== 'make') {
            return [];
        }

        if (! $this->isCalledOnModel($node, $scope)) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Called 'Model::make()' which performs unnecessary work, use 'new Model()'.")
                ->identifier('rules.noModelMake')
                ->line($node->getLine())
                ->file($scope->getFile())
                ->build(),
        ];
    }

    /**
     * Was the expression called on a Model instance?
     *
     * @param \PhpParser\Node\Expr\StaticCall $call
     * @param \PHPStan\Analyser\Scope $scope
     * @return bool
     */
    protected function isCalledOnModel(StaticCall $call, Scope $scope): bool
    {
        $class = $call->class;
        if ($class instanceof FullyQualified) {
            $type = new ObjectType($class->toString());
        } elseif ($class instanceof Expr) {
            $type = $scope->getType($class);

            if ($type->isClassStringType()->yes() && $type->getConstantStrings() !== []) {
                $type = new ObjectType($type->getConstantStrings()[0]->getValue());
            }
        } else {
            // TODO can we handle relative names, do they even occur here?
            return false;
        }

        return (new ObjectType(Model::class))
            ->isSuperTypeOf($type)
            ->yes();
    }
}

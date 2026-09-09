<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\ModelForwardsCallsExtension;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;

use function count;
use function sprintf;

/** @implements Rule<CallLike> */
class NoImplicitQueryBuilderCallRule implements Rule
{
    public function __construct(private ModelForwardsCallsExtension $modelForwardsCallsExtension)
    {
    }

    public function getNodeType(): string
    {
        return CallLike::class;
    }

    /** @return list<IdentifierRuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if ((! $node instanceof StaticCall && ! $node instanceof MethodCall && ! $node instanceof NullsafeMethodCall) || ! $node->name instanceof Identifier) {
            return [];
        }

        if ($node->getAttribute('virtualNullsafeMethodCall', false)) {
            return [];
        }

        $receiver = $node instanceof StaticCall ? $node->class : $node->var;
        $type     = $receiver instanceof Name ? $scope->resolveTypeByName($receiver) : $scope->getType($receiver);

        if ($node instanceof StaticCall) {
            $type = $type->getObjectTypeOrClassStringObjectType();
        } elseif ($node instanceof NullsafeMethodCall) {
            $type = TypeCombinator::removeNull($type);
        }

        if (! (new ObjectType(Model::class))->isSuperTypeOf($type)->yes()) {
            return [];
        }

        $reflections = $type->getObjectClassReflections();

        if (count($reflections) !== 1) {
            return [];
        }

        $model      = $reflections[0];
        $methodName = $node->name->toString();

        if ($model->hasNativeMethod($methodName)) {
            $method = $model->getNativeMethod($methodName);

            if ($scope->canCallMethod($method)) {
                return [];
            }

            $isScope = false;

            foreach ($method->getAttributes() as $attribute) {
                if ($attribute->getName() === 'Illuminate\Database\Eloquent\Attributes\Scope') {
                    $isScope = true;
                    break;
                }
            }

            if (! $isScope) {
                return [];
            }
        }

        if (! $this->modelForwardsCallsExtension->hasMethod($model, $methodName)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                'Call to %smethod %s::%s() is forwarded to the query builder.',
                $node instanceof StaticCall ? 'static ' : '',
                $model->getDisplayName(),
                $methodName,
            ))
                ->tip(sprintf(
                    'Use %s->%s() instead.',
                    $node instanceof StaticCall ? $model->getDisplayName() . '::query()' : 'newQuery()',
                    $methodName,
                ))
                ->identifier('larastan.noImplicitQueryBuilderCall')
                ->fixNode($node, static function (StaticCall|MethodCall|NullsafeMethodCall $node): MethodCall {
                    $builder = match (true) {
                        $node instanceof StaticCall => new StaticCall($node->class, 'query'),
                        $node instanceof NullsafeMethodCall => new NullsafeMethodCall($node->var, 'newQuery'),
                        default => new MethodCall($node->var, 'newQuery'),
                    };

                    return new MethodCall($builder, $node->name, $node->args);
                })
                ->build(),
        ];
    }
}

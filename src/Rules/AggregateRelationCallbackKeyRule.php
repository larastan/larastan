<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\BuilderHelper;
use Larastan\Larastan\Types\BuilderOf\BuilderOfType;
use Larastan\Larastan\Types\RelationOf\RelationOfType;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function array_keys;
use function count;
use function explode;
use function in_array;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * Detect callbacks assigned to the wrong relationship within an aggregate map.
 *
 * PHPStan's associative-array generic uses a shared relation-key template.
 * A benevolent union permits heterogeneous callbacks, but cannot establish
 * which callback belongs to which key. This checks unambiguous inline keys.
 *
 * @implements Rule<Node\Expr\CallLike>
 */
final class AggregateRelationCallbackKeyRule implements Rule
{
    public function __construct(private BuilderHelper $builderHelper)
    {
    }

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    /** @return list<RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        if (
            (! $node instanceof MethodCall && ! $node instanceof StaticCall)
            || ! $node->name instanceof Node\Identifier
            || $node->isFirstClassCallable()
            || preg_match('/^(with|load)(aggregate|count|max|min|sum|avg|exists)$/', strtolower($node->name->name)) !== 1
        ) {
            return [];
        }

        $relations = null;

        foreach ($node->getArgs() as $index => $arg) {
            if ($arg->unpack) {
                continue;
            }

            if ($arg->name === null ? $index === 0 : in_array($arg->name->toString(), ['relation', 'relations'], true)) {
                $relations = $arg->value;
                break;
            }
        }

        if (! $relations instanceof Array_ || count($relations->items) < 2) {
            return [];
        }

        $modelType = $this->modelType($node, $scope);

        // A union of possible parent models cannot establish a single builder
        // for a particular key. Keep the normal PHPStan fallback in that case.
        if ($modelType === null || count($modelType->getObjectClassReflections()) !== 1) {
            return [];
        }

        // Only enforce the extra constraint for heterogeneous relationship maps.
        // Single-key arrays are already checked by PHPStan's normal argument rule.
        $names = [];
        $items = [];

        foreach ($relations->items as $item) {
            if ($item->unpack || $item->key === null) {
                continue;
            }

            $keys = $scope->getType($item->key)->getConstantStrings();

            if (count($keys) !== 1) {
                continue;
            }

            $name     = $keys[0]->getValue();
            $segments = explode(' ', $name);

            if (count($segments) === 3 && strtolower($segments[1]) === 'as') {
                $name = $segments[0];
            }

            // Aggregate APIs call a method on the parent model directly.
            // Dotted paths and column-selection syntax are invalid at runtime,
            // and the existing relation-existence rule reports those separately.
            if ($name === '' || $name !== explode('.', $name, 2)[0] || $name !== explode(':', $name, 2)[0]) {
                continue;
            }

            $names[$name] = true;
            $items[]      = [$item, $name];
        }

        if (count($names) < 2) {
            return [];
        }

        // Resolve each concrete key once. Skip unknown relations: the existing
        // relation-existence rule owns those diagnostics.
        $builders = [];

        foreach (array_keys($names) as $name) {
            $keyType = new ConstantStringType($name);

            if ((new RelationOfType($modelType, $keyType))->resolveRelationType() === null) {
                continue;
            }

            $builders[$name] = (new BuilderOfType($modelType, $this->builderHelper, $keyType))->resolve();
        }

        if (count($builders) < 2) {
            return [];
        }

        $errors = [];

        foreach ($items as [$item, $name]) {
            if (! isset($builders[$name])) {
                continue;
            }

            $callbackType = $scope->getType($item->value);

            if (! $callbackType->isCallable()->yes()) {
                continue;
            }

            $acceptors = $callbackType->getCallableParametersAcceptors($scope);

            // Multiple signatures cannot be attributed reliably to one key.
            if (count($acceptors) !== 1) {
                continue;
            }

            $parameters = $acceptors[0]->getParameters();

            if ($parameters === []) {
                continue;
            }

            $actual   = $builders[$name];
            $declared = $parameters[0]->getType();

            if (! $declared->accepts($actual, true)->no()) {
                continue;
            }

            // Report only mismatches hidden by the shared benevolent union.
            // When a callback matches none of the builders, PHPStan's normal
            // argument checking already reports it; avoid duplicate errors.
            $fitsAnother = false;

            foreach ($builders as $otherName => $otherBuilder) {
                if ($otherName !== $name && $declared->accepts($otherBuilder, true)->yes()) {
                    $fitsAnother = true;
                    break;
                }
            }

            if (! $fitsAnother) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(sprintf(
                'Aggregate callback for relation "%s" must accept %s, but its first parameter requires %s.',
                $name,
                $actual->describe(VerbosityLevel::typeOnly()),
                $declared->describe(VerbosityLevel::typeOnly()),
            ))->identifier('larastan.aggregateCallbackType')->line($item->getStartLine())->build();
        }

        return $errors;
    }

    private function modelType(MethodCall|StaticCall $node, Scope $scope): Type|null
    {
        $receiver     = $node instanceof MethodCall ? $node->var : $node->class;
        $receiverType = $receiver instanceof Node\Name
            ? $scope->resolveTypeByName($receiver)
            : $scope->getType($receiver);

        if ((new ObjectType(Builder::class))->isSuperTypeOf($receiverType)->yes()) {
            return $receiverType->getTemplateType(Builder::class, 'TModel');
        }

        if ((new ObjectType(Collection::class))->isSuperTypeOf($receiverType)->yes()) {
            return $receiverType->getTemplateType(Collection::class, 'TModel');
        }

        return (new ObjectType(Model::class))->isSuperTypeOf($receiverType)->yes() ? $receiverType : null;
    }
}

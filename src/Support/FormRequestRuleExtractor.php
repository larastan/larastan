<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\ValidationRule;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;

use function array_filter;
use function array_pop;
use function array_push;
use function array_reverse;
use function count;
use function explode;
use function str_contains;

/**
 * @internal
 *
 * @phpstan-import-type RuleTree from RuleTreeBuilder
 */
final class FormRequestRuleExtractor
{
    public function __construct(
        private Parser $parser,
        private ScopeFactory $scopeFactory,
        private Container $container,
    ) {
    }

    /** @return RuleTree|null */
    public function extract(ClassReflection $classReflection): array|null
    {
        if (! $classReflection->hasNativeMethod('rules')) {
            return null;
        }

        $reflection     = $classReflection->getNativeMethod('rules');
        $declaringClass = $reflection->getDeclaringClass();
        $nativeClass    = $declaringClass->getNativeReflection();
        $methodName     = $reflection->getName();
        $className      = $declaringClass->getName();
        $nativeMethod   = $nativeClass->getMethod($methodName);
        $file           = $nativeMethod->getFileName();
        $line           = $nativeMethod->getStartLine();

        if ($file === false || $line === false) {
            return null;
        }

        $nodes  = $this->parser->parseFile($file);
        $class  = (new NodeFinder())->findFirst($nodes, static fn (Node $node): bool => $node instanceof ClassLike
            && $node->getMethod($methodName)?->getStartLine() === $line);
        $method = $class instanceof ClassLike ? $class->getMethod($methodName) : null;

        if ($method === null) {
            return null;
        }

        // Trait code keeps its source namespace, but self/static belong to the consuming class.
        $isolatedClass                 = new Stmt\Class_(
            $nativeClass->getShortName(),
            ['stmts' => [$method]],
            ['startLine' => $nativeClass->getStartLine()],
        );
        $isolatedClass->namespacedName = new Name($className);
        $namespace                     = new Stmt\Namespace_($class->namespacedName?->slice(0, -1), [$isolatedClass]);
        $returns                       = [];
        // Resolve lazily: constructor injection creates a cycle through the type extensions.
        $this->container->getByType(NodeScopeResolver::class)->processNodes(
            // PHPStan's strict_types scope transition must happen before entering the namespace.
            [...array_filter($nodes, static fn (Stmt $node): bool => $node instanceof Stmt\Declare_), $namespace],
            $this->scopeFactory->create(ScopeContext::create($declaringClass->getFileName() ?? $file)),
            static function (Node $node, Scope $scope) use ($methodName, $className, &$returns): void {
                $function = $scope->getFunction();

                if (
                    ! $node instanceof Return_ || $node->expr === null || $scope->isInAnonymousFunction()
                    || ! $function instanceof MethodReflection || $function->getName() !== $methodName
                    || $function->getDeclaringClass()->getName() !== $className
                ) {
                    return;
                }

                // This callback scope preserves assignments within the returned expression.
                $returns[] = self::fromExpression($node->expr, $scope);
            },
        );

        return RuleTreeBuilder::mergeAlternatives($returns)?->build();
    }

    private static function fromExpression(Expr $expression, Scope $scope): RuleTreeBuilder|null
    {
        if (! $expression instanceof Array_) {
            return RuleTreeBuilder::fromType($scope->getType($expression));
        }

        $builder = new RuleTreeBuilder();

        // The builder sees later writes first, including values unpacked from another array.
        foreach (array_reverse($expression->items) as $item) {
            if ($item->unpack) {
                $builder->unpack($scope->getType($item->value));
            } elseif ($item->key === null) {
                $builder->append();
            } else {
                $builder->add($scope->getType($item->key), static fn (): ValidationRule => self::readRule($item->value, $scope));
            }
        }

        return $builder;
    }

    private static function readRule(Expr $expression, Scope $scope): ValidationRule
    {
        $names = [];

        if ($expression instanceof Array_) {
            foreach ($expression->items as $index => $item) {
                if ($item->key !== null || $item->unpack) {
                    $names = []; // Explicit keys and spreads can change rule indices.
                    break;
                }

                $name = self::parameterizedName($item->value, $scope);

                if ($name === null) {
                    continue;
                }

                $names[$index] = $name;
            }
        }

        return ValidationRuleFactory::fromType($scope->getType($expression), parameterizedRules: $names) ?? ValidationRuleFactory::make([]);
    }

    /** Read only the known prefix through ':', not the runtime parameters that follow it. */
    private static function parameterizedName(Expr $expression, Scope $scope): string|null
    {
        $parts  = [$expression];
        $prefix = '';

        while ($parts !== []) {
            $part = array_pop($parts);

            if ($part instanceof Concat) {
                // Push backwards so popping visits string parts in source order.
                $parts[] = $part->right;
                $parts[] = $part->left;
            } elseif ($part instanceof InterpolatedString) {
                array_push($parts, ...array_reverse($part->parts));
            } else {
                $type    = $part instanceof InterpolatedStringPart ? new ConstantStringType($part->value) : $scope->getType($part)->toString();
                $strings = $type->getConstantStrings();

                if (count($strings) !== 1 || ! $type->equals($strings[0])) {
                    return null;
                }

                $prefix .= $strings[0]->getValue();

                if (str_contains($prefix, ':')) {
                    return explode(':', $prefix, 2)[0];
                }
            }
        }

        return null;
    }
}

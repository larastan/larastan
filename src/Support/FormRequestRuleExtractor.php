<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Larastan\Larastan\Support\Validation\ValidationRule;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_reverse;
use function array_shift;
use function array_values;
use function count;

/** @internal */
final class FormRequestRuleExtractor
{
    public function __construct(
        private Parser $parser,
        private ScopeFactory $scopeFactory,
        private NodeScopeResolver $nodeScopeResolver,
    ) {
    }

    /** @return array<string, ValidationRule>|null */
    public function extract(ClassReflection $classReflection): array|null
    {
        if (! $classReflection->hasNativeMethod('rules')) {
            return null;
        }

        $rulesMethod    = $classReflection->getNativeMethod('rules');
        $declaringClass = $rulesMethod->getDeclaringClass();
        $nativeMethod   = $declaringClass->getNativeReflection()->getMethod($rulesMethod->getName());
        $fileName       = $nativeMethod->getFileName();
        $startLine      = $nativeMethod->getStartLine();

        if ($fileName === false || $startLine === false) {
            return null;
        }

        $methodName = $rulesMethod->getName();
        $nodes      = $this->isolateClassMethod(
            $this->parser->parseFile($fileName),
            static function (ClassLike $class) use ($methodName, $startLine): ClassMethod|null {
                foreach ($class->stmts as $statement) {
                    if (
                        $statement instanceof ClassMethod
                        && $statement->name->toString() === $methodName
                        && $statement->getStartLine() === $startLine
                    ) {
                        return $statement;
                    }
                }

                return null;
            },
        );

        if ($nodes === null) {
            return null;
        }

        $nodeFinder = new NodeFinder();
        $trait      = $nodeFinder->findFirstInstanceOf($nodes, Trait_::class);

        if ($trait !== null) {
            $method        = $nodeFinder->findFirstInstanceOf($nodes, ClassMethod::class);
            $classFileName = $declaringClass->getFileName();

            if ($method === null || $classFileName === null) {
                return null;
            }

            $className = $declaringClass->getName();
            $nodes     = $this->isolateClassMethod(
                $this->parser->parseFile($classFileName),
                static function (ClassLike $class, string $namespace) use ($className, $method): ClassMethod|null {
                    if ($class->name === null) {
                        return null;
                    }

                    $candidateName = $namespace === ''
                        ? $class->name->toString()
                        : $namespace . '\\' . $class->name->toString();

                    return $candidateName === $className ? $method : null;
                },
            );

            if ($nodes === null) {
                return null;
            }

            $fileName = $classFileName;
        }

        $scope   = $this->scopeFactory->create(ScopeContext::create($fileName));
        $returns = [];

        $this->nodeScopeResolver->processNodes(
            $nodes,
            $scope,
            static function (Node $node, Scope $scope) use ($rulesMethod, &$returns): void {
                if (! $node instanceof Return_ || $node->expr === null || $scope->isInAnonymousFunction()) {
                    return;
                }

                $function = $scope->getFunction();

                if (! $function instanceof MethodReflection || $function->getName() !== $rulesMethod->getName()) {
                    return;
                }

                $returns[] = self::extractReturn($node, $scope);
            },
        );

        return self::mergeReturns($returns);
    }

    /**
     * @param array<Stmt>                                     $nodes
     * @param callable(ClassLike, string): (ClassMethod|null) $findMethod
     *
     * @return array<Stmt>|null
     */
    private function isolateClassMethod(
        array $nodes,
        callable $findMethod,
        string $namespace = '',
    ): array|null {
        foreach ($nodes as $node) {
            if ($node instanceof Namespace_) {
                $isolated = $this->isolateClassMethod(
                    $node->stmts,
                    $findMethod,
                    $node->name?->toString() ?? '',
                );

                if ($isolated === null) {
                    continue;
                }

                $node->stmts = $isolated;

                return array_values(array_filter(
                    $nodes,
                    static fn (Stmt $statement): bool => $statement instanceof Stmt\Declare_ || $statement === $node,
                ));
            }

            if (! $node instanceof ClassLike) {
                continue;
            }

            $method = $findMethod($node, $namespace);

            if ($method === null) {
                continue;
            }

            $node->stmts = [$method];

            return array_values(array_filter(
                $nodes,
                static fn (Stmt $statement): bool => self::isContextStatement($statement) || $statement === $node,
            ));
        }

        return null;
    }

    private static function isContextStatement(Stmt $statement): bool
    {
        return $statement instanceof Stmt\Declare_
            || $statement instanceof Stmt\Use_
            || $statement instanceof Stmt\GroupUse
            || $statement instanceof Stmt\Const_;
    }

    /** @return array<string, ValidationRule>|null */
    private static function extractReturn(Return_ $return, Scope $scope): array|null
    {
        if ($return->expr === null) {
            return null;
        }

        if (! $return->expr instanceof Array_) {
            return self::extractConstantArrays($scope->getType($return->expr));
        }

        $rules = [];

        foreach (array_reverse($return->expr->items) as $item) {
            if ($item->unpack) {
                $unpackedRules = self::extractConstantArrays($scope->getType($item->value));
                $rules        += array_reverse($unpackedRules ?? [], true);

                continue;
            }

            if ($item->key === null) {
                continue;
            }

            $propertyName = self::extractConstantString($scope->getType($item->key));

            if ($propertyName === null || array_key_exists($propertyName, $rules)) {
                continue;
            }

            // Keep unknown rules as null so they still override earlier entries for the same key.
            $rules[$propertyName] = ValidationRuleFactory::fromType($scope->getType($item->value));
        }

        return array_reverse(array_filter($rules), true);
    }

    /** @return array<string, ValidationRule>|null */
    private static function extractConstantArrays(Type $type): array|null
    {
        return self::mergeReturns(array_map(self::extractConstantArray(...), $type->getConstantArrays()));
    }

    /** @return array<string, ValidationRule> */
    private static function extractConstantArray(ConstantArrayType $array): array
    {
        $rules = [];

        foreach ($array->getKeyTypes() as $index => $keyType) {
            if ($array->isOptionalKey($index)) {
                continue;
            }

            $propertyName = self::extractConstantString($keyType);

            if ($propertyName === null) {
                continue;
            }

            $rule = ValidationRuleFactory::fromType($array->getValueTypes()[$index]);

            if ($rule === null) {
                continue;
            }

            $rules[$propertyName] = $rule;
        }

        return $rules;
    }

    /**
     * @param list<array<string, ValidationRule>|null> $returns
     *
     * @return array<string, ValidationRule>|null
     */
    private static function mergeReturns(array $returns): array|null
    {
        $merged = array_shift($returns);

        if ($merged === null) {
            return null;
        }

        foreach ($returns as $rules) {
            if ($rules === null) {
                return null;
            }

            foreach ($merged as $key => $rule) {
                if (! array_key_exists($key, $rules)) {
                    unset($merged[$key]);

                    continue;
                }

                $mergedRule = self::mergeRules($rule, $rules[$key]);

                if ($mergedRule === null) {
                    unset($merged[$key]);

                    continue;
                }

                $merged[$key] = $mergedRule;
            }
        }

        return $merged;
    }

    private static function mergeRules(ValidationRule $left, ValidationRule $right): ValidationRule|null
    {
        if ($left->allowedKeys !== $right->allowedKeys || $left->anyOfRuleGroups !== $right->anyOfRuleGroups) {
            return null;
        }

        $constraintType = $left->constraintType === null || $right->constraintType === null
            ? null
            : TypeCombinator::union($left->constraintType, $right->constraintType);

        $type = TypeCombinator::union($left->type, $right->type);

        if ($left->type instanceof BenevolentUnionType || $right->type instanceof BenevolentUnionType) {
            $type = TypeUtils::toBenevolentUnion($type);
        }

        return new ValidationRule(
            $type,
            $left->nullable || $right->nullable,
            $left->possiblyUndefined || $right->possiblyUndefined,
            $left->required && $right->required,
            $constraintType,
            $left->allowedKeys,
            $left->anyOfRuleGroups,
            $left->rejectsNull && $right->rejectsNull,
            $left->possiblyExcluded || $right->possiblyExcluded,
            $left->excluded && $right->excluded,
            $left->degraded || $right->degraded,
        );
    }

    private static function extractConstantString(Type $type): string|null
    {
        $constantStrings = $type->getConstantStrings();

        if (count($constantStrings) !== 1) {
            return null;
        }

        return $constantStrings[0]->getValue();
    }
}

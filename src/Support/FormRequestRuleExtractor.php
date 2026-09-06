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
use PHPStan\DependencyInjection\Container;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;

use function array_diff_key;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_reverse;
use function array_shift;
use function array_values;
use function count;

/**
 * @internal
 *
 * @phpstan-type ExtractedRules array{rules: array<string, ValidationRule>, unsealed: bool}
 */
final class FormRequestRuleExtractor
{
    public function __construct(
        private Parser $parser,
        private ScopeFactory $scopeFactory,
        private Container $container,
    ) {
    }

    /** @return ExtractedRules|null */
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

        $this->container->getByType(NodeScopeResolver::class)->processNodes(
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

    /** @return ExtractedRules|null */
    private static function extractReturn(Return_ $return, Scope $scope): array|null
    {
        if ($return->expr === null) {
            return null;
        }

        if (! $return->expr instanceof Array_) {
            return self::extractConstantArrays($scope->getType($return->expr));
        }

        $rules                   = [];
        $unsealed                = false;
        $unknownMayOverridePrior = false;

        foreach (array_reverse($return->expr->items) as $item) {
            if ($item->unpack) {
                $unpackedRules = self::extractConstantArrays($scope->getType($item->value));

                if ($unpackedRules === null) {
                    $unsealed                = true;
                    $unknownMayOverridePrior = true;

                    continue;
                }

                if (! $unknownMayOverridePrior) {
                    $rules += array_reverse($unpackedRules['rules'], true);
                }

                if ($unpackedRules['unsealed']) {
                    $unsealed                = true;
                    $unknownMayOverridePrior = true;
                }

                continue;
            }

            if ($item->key === null) {
                $unsealed = true;

                continue;
            }

            $keyType      = $scope->getType($item->key);
            $propertyName = self::extractConstantString($keyType);

            if ($propertyName === null) {
                $unsealed = true;

                if (! $keyType->toArrayKey()->isInteger()->yes()) {
                    $unknownMayOverridePrior = true;
                }

                continue;
            }

            if (array_key_exists($propertyName, $rules) || $unknownMayOverridePrior) {
                continue;
            }

            $rules[$propertyName] = ValidationRuleFactory::fromType($scope->getType($item->value))
                ?? ValidationRuleFactory::make([]);
        }

        return [
            'rules' => array_reverse($rules, true),
            'unsealed' => $unsealed,
        ];
    }

    /** @return ExtractedRules|null */
    private static function extractConstantArrays(Type $type): array|null
    {
        if (! $type->isConstantArray()->yes()) {
            return null;
        }

        return self::mergeReturns(array_map(self::extractConstantArray(...), $type->getConstantArrays()));
    }

    /** @return ExtractedRules */
    private static function extractConstantArray(ConstantArrayType $array): array
    {
        $rules    = [];
        $unsealed = false;

        foreach ($array->getKeyTypes() as $index => $keyType) {
            if ($array->isOptionalKey($index)) {
                $unsealed = true;

                continue;
            }

            $propertyName = self::extractConstantString($keyType);

            if ($propertyName === null) {
                $unsealed = true;

                continue;
            }

            $rules[$propertyName] = ValidationRuleFactory::fromType($array->getValueTypes()[$index])
                ?? ValidationRuleFactory::make([]);
        }

        return ['rules' => $rules, 'unsealed' => $unsealed];
    }

    /**
     * @param list<ExtractedRules|null> $returns
     *
     * @return ExtractedRules|null
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

            $merged['unsealed'] = $merged['unsealed']
                || $rules['unsealed']
                || array_diff_key($merged['rules'], $rules['rules']) !== []
                || array_diff_key($rules['rules'], $merged['rules']) !== [];

            foreach ($merged['rules'] as $key => $rule) {
                if (! array_key_exists($key, $rules['rules'])) {
                    unset($merged['rules'][$key]);

                    continue;
                }

                $mergedRule = self::mergeRules($rule, $rules['rules'][$key]);

                if ($mergedRule === null) {
                    unset($merged['rules'][$key]);
                    $merged['unsealed'] = true;

                    continue;
                }

                $merged['rules'][$key] = $mergedRule;
            }
        }

        return $merged;
    }

    private static function mergeRules(ValidationRule $left, ValidationRule $right): ValidationRule|null
    {
        if (! $left->hasSameStructure($right)) {
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
            type: $type,
            nullable: $left->nullable || $right->nullable,
            possiblyUndefined: $left->possiblyUndefined || $right->possiblyUndefined,
            required: $left->required && $right->required,
            constraintType: $constraintType,
            allowedKeys: $left->allowedKeys,
            anyOfRuleGroups: $left->anyOfRuleGroups,
            rejectsNull: $left->rejectsNull && $right->rejectsNull,
            possiblyExcluded: $left->possiblyExcluded || $right->possiblyExcluded,
            excluded: $left->excluded && $right->excluded,
            degraded: $left->degraded || $right->degraded,
            prunesUnvalidatedKeys: $left->prunesUnvalidatedKeys === $right->prunesUnvalidatedKeys
                ? $left->prunesUnvalidatedKeys
                : null,
        );
    }

    private static function extractConstantString(Type $type): string|null
    {
        $constantStrings = $type->getConstantStrings();

        if (count($constantStrings) === 1 && $type->equals($constantStrings[0])) {
            $arrayKeyStrings = $constantStrings[0]->toArrayKey()->getConstantStrings();

            return count($arrayKeyStrings) === 1 ? $arrayKeyStrings[0]->getValue() : null;
        }

        return null;
    }
}

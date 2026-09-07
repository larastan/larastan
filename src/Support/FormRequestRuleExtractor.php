<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Support\Str;
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
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_pop;
use function array_reverse;
use function array_shift;
use function count;
use function explode;
use function implode;
use function is_string;
use function preg_split;
use function str_contains;

/**
 * @internal
 *
 * @phpstan-type ExtractedRules array{rules: array<string, ValidationRule>, unsealed: bool}
 */
final class FormRequestRuleExtractor
{
    /** Dots separate the segments of a rule key unless they are escaped. */
    private const KEY_SEGMENT_PATTERN = '/(?<!\\\\)\./';

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
        $methodName     = $rulesMethod->getName();
        $declaringClass = $rulesMethod->getDeclaringClass();
        $nativeClass    = $declaringClass->getNativeReflection();
        $nativeMethod   = $nativeClass->getMethod($methodName);
        $fileName       = $nativeMethod->getFileName();
        $startLine      = $nativeMethod->getStartLine();

        if ($fileName === false || $startLine === false) {
            return null;
        }

        $nodes     = $this->parser->parseFile($fileName);
        $classNode = (new NodeFinder())->findFirst(
            $nodes,
            static fn (Node $node): bool => $node instanceof ClassLike
                && $node->getMethod($methodName)?->getStartLine() === $startLine,
        );
        $method    = $classNode instanceof ClassLike ? $classNode->getMethod($methodName) : null;

        if ($method === null) {
            return null;
        }

        $className             = $declaringClass->getName();
        $class                 = new Stmt\Class_(
            $nativeClass->getShortName(),
            ['stmts' => [$method]],
            ['startLine' => $nativeClass->getStartLine()],
        );
        $class->namespacedName = new Name($className);
        $namespace             = new Stmt\Namespace_($classNode->namespacedName?->slice(0, -1), [$class]);
        $returns               = [];

        $this->container->getByType(NodeScopeResolver::class)->processNodes(
            [...array_filter($nodes, static fn (Stmt $node): bool => $node instanceof Stmt\Declare_), $namespace],
            $this->scopeFactory->create(ScopeContext::create($declaringClass->getFileName() ?? $fileName)),
            static function (Node $node, Scope $scope) use ($methodName, $className, &$returns): void {
                $function = $scope->getFunction();

                if (
                    ! $node instanceof Return_
                    || $node->expr === null
                    || $scope->isInAnonymousFunction()
                    || ! $function instanceof MethodReflection
                    || $function->getName() !== $methodName
                    || $function->getDeclaringClass()->getName() !== $className
                ) {
                    return;
                }

                $returns[] = self::extractReturn($node->expr, $scope);
            },
        );

        return self::mergeReturns($returns);
    }

    /** @return ExtractedRules|null */
    private static function extractReturn(Expr $expression, Scope $scope): array|null
    {
        if (! $expression instanceof Array_) {
            return self::extractConstantArrays($scope->getType($expression));
        }

        // Walking backwards keeps the last entry for a key, the one Laravel validates against.
        $rules                   = [];
        $unsealed                = false;
        $unknownMayOverridePrior = false;

        foreach (array_reverse($expression->items) as $item) {
            if ($item->unpack) {
                $unpackedType  = $scope->getType($item->value);
                $unpackedRules = self::extractConstantArrays($unpackedType);

                if ($unpackedRules !== null && ! $unknownMayOverridePrior) {
                    $rules += array_reverse($unpackedRules['rules'], true);
                }

                if ($unpackedRules !== null && ! $unpackedRules['unsealed']) {
                    continue;
                }

                $unknownKeys = $unpackedType->getIterableKeyType();
            } elseif ($item->key === null) {
                // Appended values are renumbered, so they never name a property.
                $unsealed = true;

                continue;
            } else {
                $keyType      = $scope->getType($item->key);
                $propertyName = self::extractConstantString($keyType);

                if ($propertyName !== null) {
                    if (! array_key_exists($propertyName, $rules) && ! $unknownMayOverridePrior) {
                        $rules[$propertyName] = self::extractRule($item->value, $scope)
                            ?? ValidationRuleFactory::make([]);
                    }

                    continue;
                }

                $unknownKeys = $keyType->toArrayKey();
            }

            // An unresolved key can name any property: it invalidates the nested rules
            // it could redefine, and unless it is numeric it also shadows earlier entries.
            $unsealed                = true;
            $unknownMayOverridePrior = $unknownMayOverridePrior || ! $unknownKeys->isInteger()->yes();
            $rules                   = self::removeUnknownDescendants($rules, $unknownKeys);
        }

        return [
            'rules' => array_reverse($rules, true),
            'unsealed' => $unsealed,
        ];
    }

    private static function extractRule(Expr $expression, Scope $scope): ValidationRule|null
    {
        $parameterizedRules = [];

        if ($expression instanceof Array_) {
            foreach ($expression->items as $index => $item) {
                // Explicit keys and unpacking can change the final rule indices.
                if ($item->key !== null || $item->unpack) {
                    $parameterizedRules = [];
                    break;
                }

                $name = self::extractParameterizedRuleName($item->value, $scope);

                if ($name === null) {
                    continue;
                }

                $parameterizedRules[$index] = $name;
            }
        }

        return ValidationRuleFactory::fromType($scope->getType($expression), parameterizedRules: $parameterizedRules);
    }

    /** Read the rule name of a rule whose parameters are only known at runtime, such as `'max:' . $limit`. */
    private static function extractParameterizedRuleName(Expr $expression, Scope $scope): string|null
    {
        $prefix = '';

        foreach (self::concatenatedStrings($expression, $scope) as $chunk) {
            if ($chunk === null) {
                return null;
            }

            $prefix .= $chunk;

            if (str_contains($prefix, ':')) {
                return explode(':', $prefix, 2)[0];
            }
        }

        return null;
    }

    /**
     * Flatten a concatenation or interpolation into its parts, in source order.
     *
     * @return iterable<string|null> null for a part that is not a constant string
     */
    private static function concatenatedStrings(Expr|InterpolatedStringPart $node, Scope $scope): iterable
    {
        if ($node instanceof InterpolatedStringPart) {
            yield $node->value;
        } elseif ($node instanceof Concat) {
            yield from self::concatenatedStrings($node->left, $scope);
            yield from self::concatenatedStrings($node->right, $scope);
        } elseif ($node instanceof InterpolatedString) {
            foreach ($node->parts as $part) {
                yield from self::concatenatedStrings($part, $scope);
            }
        } else {
            $type    = $scope->getType($node)->toString();
            $strings = $type->getConstantStrings();

            yield count($strings) === 1 && $type->equals($strings[0]) ? $strings[0]->getValue() : null;
        }
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
            $propertyName = $array->isOptionalKey($index) ? null : self::extractConstantString($keyType);

            if ($propertyName === null) {
                $unsealed = true;

                continue;
            }

            $rules[$propertyName] = ValidationRuleFactory::fromType($array->getValueTypes()[$index])
                ?? ValidationRuleFactory::make([]);
        }

        return [
            'rules' => $unsealed ? self::removeUnknownDescendants($rules, $array->getIterableKeyType()) : $rules,
            'unsealed' => $unsealed,
        ];
    }

    /**
     * Drop rules nested under a path that an unresolved key could redefine.
     *
     * @param array<string, ValidationRule> $rules
     *
     * @return array<string, ValidationRule>
     */
    private static function removeUnknownDescendants(array $rules, Type $unknownKeys): array
    {
        foreach ($rules as $key => $rule) {
            $segments = self::keySegments($key);
            array_pop($segments);

            while ($segments !== []) {
                $ancestor = implode('.', $segments);

                // A later explicit ancestor rule overrides the unknown source.
                if (! array_key_exists($ancestor, $rules) && self::mayDefineKey($unknownKeys, $ancestor, count($segments))) {
                    unset($rules[$key]);

                    break;
                }

                array_pop($segments);
            }
        }

        return $rules;
    }

    /** @param int $depth number of segments in $key, which a wildcard source has to match exactly */
    private static function mayDefineKey(Type $unknownKeys, string $key, int $depth): bool
    {
        if (! $unknownKeys->isSuperTypeOf((new ConstantStringType($key))->toArrayKey())->no()) {
            return true;
        }

        foreach ($unknownKeys->getConstantStrings() as $unknownKey) {
            if (count(self::keySegments($unknownKey->getValue())) === $depth && Str::is($unknownKey->getValue(), $key)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<string> */
    private static function keySegments(string $key): array
    {
        $segments = preg_split(self::KEY_SEGMENT_PATTERN, $key);

        return $segments === false ? [$key] : $segments;
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

            $merged['unsealed'] = $merged['unsealed'] || $rules['unsealed'];
            $uncertainKeys      = [];

            foreach ($merged['rules'] + $rules['rules'] as $key => $rule) {
                $mergedRule = isset($merged['rules'][$key], $rules['rules'][$key])
                    ? $rule->merge($rules['rules'][$key])
                    : null;

                if ($mergedRule === null) {
                    $uncertainKeys[] = new ConstantStringType($key);
                    unset($merged['rules'][$key]);
                    $merged['unsealed'] = true;

                    continue;
                }

                $merged['rules'][$key] = $mergedRule;
            }

            if ($uncertainKeys === []) {
                continue;
            }

            $merged['rules'] = self::removeUnknownDescendants($merged['rules'], TypeCombinator::union(...$uncertainKeys));
        }

        return $merged;
    }

    private static function extractConstantString(Type $type): string|null
    {
        $values = $type->toArrayKey()->getConstantScalarValues();

        return count($values) === 1 && is_string($values[0]) ? $values[0] : null;
    }
}

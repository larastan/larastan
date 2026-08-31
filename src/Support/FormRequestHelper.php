<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\RuleTreeTypeResolver;
use Larastan\Larastan\Support\Validation\ValidationRuleFactory;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Type;

use function array_key_exists;
use function array_map;
use function count;

/** @internal */
final class FormRequestHelper
{
    /** @var array<class-string<FormRequest>, array<string, Type>> */
    private array $properties = [];

    /** @var array<class-string<FormRequest>, true> */
    private array $resolving = [];

    public function __construct(
        private RuleTreeTypeResolver $treeTypeResolver,
        private Parser $parser,
        private ScopeFactory $scopeFactory,
        private NodeScopeResolver $nodeScopeResolver,
    ) {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (! array_key_exists($className, $this->properties)) {
            if (array_key_exists($className, $this->resolving)) {
                return false;
            }

            $this->resolving[$className] = true;

            try {
                $this->properties[$className] = $this->parseProperties($classReflection);
            } finally {
                unset($this->resolving[$className]);
            }
        }

        return array_key_exists($propertyName, $this->properties[$className]);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): Type
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        return $this->properties[$className][$propertyName];
    }

    /** @return array<string, Type> */
    private function parseProperties(ClassReflection $classReflection): array
    {
        if (! $classReflection->hasNativeMethod('rules')) {
            return [];
        }

        $rulesMethod    = $classReflection->getNativeMethod('rules');
        $declaringClass = $rulesMethod->getDeclaringClass();
        $fileName       = $declaringClass->getFileName();

        if ($fileName === null) {
            return [];
        }

        $stmts = $this->parser->parseFile($fileName);
        $scope = $this->scopeFactory->create(ScopeContext::create($fileName));

        $flatRules  = [];
        $foundRules = false;

        $this->nodeScopeResolver->processNodes(
            $stmts,
            $scope,
            static function (Node $node, Scope $scope) use ($rulesMethod, $declaringClass, &$flatRules, &$foundRules): void {
                if ($foundRules || ! $node instanceof Return_ || ! $node->expr instanceof Array_) {
                    return;
                }

                $function = $scope->getFunction();

                if (
                    ! $function instanceof MethodReflection
                    || $function->getName() !== $rulesMethod->getName()
                    || $function->getDeclaringClass()->getName() !== $declaringClass->getName()
                ) {
                    return;
                }

                $foundRules = true;

                foreach ($node->expr->items as $item) {
                    if ($item->unpack || $item->key === null) {
                        continue;
                    }

                    $propertyName = self::extractConstantString($scope->getType($item->key));

                    if ($propertyName === null) {
                        continue;
                    }

                    $rules = self::extractConstantRules($scope->getType($item->value));

                    if ($rules === null) {
                        continue;
                    }

                    $flatRules[$propertyName] = ValidationRuleFactory::make($rules);
                }
            },
        );

        return array_map(
            $this->treeTypeResolver->resolveTopLevel(...),
            RuleTreeBuilder::build($flatRules),
        );
    }

    /** @return string|list<string>|null */
    private static function extractConstantRules(Type $type): string|array|null
    {
        $rule = self::extractConstantString($type);

        if ($rule !== null) {
            return $rule;
        }

        $constantArrays = $type->getConstantArrays();

        if (count($constantArrays) !== 1) {
            return null;
        }

        $rules = [];

        foreach ($constantArrays[0]->getValueTypes() as $valueType) {
            $rule = self::extractConstantString($valueType);

            if ($rule === null) {
                continue;
            }

            $rules[] = $rule;
        }

        return $rules;
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

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\RuleTreeNode;
use Larastan\Larastan\Support\Validation\RuleTreeTypeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_key_exists;

/** @internal */
final class FormRequestHelper
{
    /** @var array<class-string<FormRequest>, array{nodes: array<string, RuleTreeNode>, unsealed: bool}|null> */
    private array $trees = [];

    /** @var array<class-string<FormRequest>, array<string, Type>> */
    private array $rawProperties = [];

    /** @var array<class-string<FormRequest>, Type> */
    private array $validatedData = [];

    /** @var array<class-string<FormRequest>, true> */
    private array $resolving = [];

    public function __construct(
        private RuleTreeTypeResolver $treeTypeResolver,
        private FormRequestRuleExtractor $ruleExtractor,
    ) {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (! array_key_exists($className, $this->rawProperties)) {
            if (array_key_exists($className, $this->resolving)) {
                return false;
            }

            $tree = $this->getTree($classReflection);

            $this->rawProperties[$className] = $tree === null
                ? []
                : $this->treeTypeResolver->resolveRawProperties($tree['nodes']);
        }

        return array_key_exists($propertyName, $this->rawProperties[$className]);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): Type
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        return $this->rawProperties[$className][$propertyName];
    }

    public function getValidatedDataType(Type $formRequestType, string $methodName, Scope $scope): Type|null
    {
        $classReflections = $formRequestType->getObjectClassReflections();
        $types            = [];

        if ($classReflections === []) {
            return null;
        }

        foreach ($classReflections as $classReflection) {
            if (
                ! $classReflection->is(FormRequest::class)
                || $classReflection->getMethod($methodName, $scope)->getDeclaringClass()->getName() !== FormRequest::class
            ) {
                return null;
            }

            $type = $this->getValidatedDataTypeForClass($classReflection);

            if ($type === null) {
                return null;
            }

            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }

    private function getValidatedDataTypeForClass(ClassReflection $classReflection): Type|null
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (array_key_exists($className, $this->resolving)) {
            return null;
        }

        if (! array_key_exists($className, $this->validatedData)) {
            $tree = $this->getTree($classReflection);

            if ($tree === null) {
                return null;
            }

            $this->validatedData[$className] = $this->treeTypeResolver->resolveValidatedData(
                $tree['nodes'],
                $tree['unsealed'],
            );
        }

        return $this->validatedData[$className];
    }

    /** @return array{nodes: array<string, RuleTreeNode>, unsealed: bool}|null */
    private function getTree(ClassReflection $classReflection): array|null
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (array_key_exists($className, $this->trees)) {
            return $this->trees[$className];
        }

        $this->resolving[$className] = true;

        try {
            $extracted = $this->ruleExtractor->extract($classReflection);

            if ($extracted === null) {
                return $this->trees[$className] = null;
            }

            $nodes = RuleTreeBuilder::build($extracted['rules']);

            // Root wildcards can exclude or otherwise change exact sibling fields.
            if (isset($nodes[RuleTreeNode::WILDCARD])) {
                return $this->trees[$className] = ['nodes' => [], 'unsealed' => true];
            }

            return $this->trees[$className] = ['nodes' => $nodes, 'unsealed' => $extracted['unsealed']];
        } finally {
            unset($this->resolving[$className]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\Validation\RuleTreeBuilder;
use Larastan\Larastan\Support\Validation\RuleTreeNode;
use Larastan\Larastan\Support\Validation\RuleTreeTypeResolver;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Type;

use function array_key_exists;

/** @internal */
final class FormRequestHelper
{
    /** @var array<class-string<FormRequest>, array<string, RuleTreeNode>|null> */
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
                : $this->treeTypeResolver->resolveRawProperties($tree);
        }

        return array_key_exists($propertyName, $this->rawProperties[$className]);
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): Type
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        return $this->rawProperties[$className][$propertyName];
    }

    public function getValidatedDataType(ClassReflection $classReflection): Type|null
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

            $this->validatedData[$className] = $this->treeTypeResolver->resolveValidatedData($tree);
        }

        return $this->validatedData[$className];
    }

    /** @return array<string, RuleTreeNode>|null */
    private function getTree(ClassReflection $classReflection): array|null
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (array_key_exists($className, $this->trees)) {
            return $this->trees[$className];
        }

        $this->resolving[$className] = true;

        try {
            $rules = $this->ruleExtractor->extract($classReflection);

            return $this->trees[$className] = $rules === null ? null : RuleTreeBuilder::build($rules);
        } finally {
            unset($this->resolving[$className]);
        }
    }
}

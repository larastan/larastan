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
use function array_map;

/** @internal */
final class FormRequestHelper
{
    /** @var array<class-string<FormRequest>, array<string, Type>> */
    private array $properties = [];

    /** @var array<class-string<FormRequest>, array<string, RuleTreeNode>|null> */
    private array $trees = [];

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

        if (! array_key_exists($className, $this->properties)) {
            if (array_key_exists($className, $this->resolving)) {
                return false;
            }

            $this->resolving[$className] = true;

            try {
                $tree = $this->getTree($classReflection);

                $this->properties[$className] = $tree === null
                    ? []
                    : array_map($this->treeTypeResolver->resolveTopLevel(...), $tree);
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

    /** @return array<string, RuleTreeNode>|null */
    private function getTree(ClassReflection $classReflection): array|null
    {
        /** @var class-string<FormRequest> $className */
        $className = $classReflection->getName();

        if (! array_key_exists($className, $this->trees)) {
            $rules = $this->ruleExtractor->extract($classReflection);

            $this->trees[$className] = $rules === null ? null : RuleTreeBuilder::build($rules);
        }

        return $this->trees[$className];
    }
}

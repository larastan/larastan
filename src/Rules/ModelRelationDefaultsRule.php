<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Database\Eloquent\Model;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Type\ObjectType;

use function array_merge;

/** @implements Rule<InClassNode> */
final class ModelRelationDefaultsRule implements Rule
{
    public function __construct(
        private RelationExistenceHelper $relationExistenceHelper,
        private InitializerExprTypeResolver $initializerExprTypeResolver,
    ) {
    }

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /** @return RuleError[] */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if (! $class->isSubclassOf(Model::class) || $class->isAbstract()) {
            return [];
        }

        $errors = [];

        foreach (['with', 'withCount'] as $name) {
            $property = $this->findProperty($class, $name);

            if ($property === null) {
                continue;
            }

            $default = $property->getDefaultValueExpression();

            $type = $this->initializerExprTypeResolver->getType($default, InitializerExprContext::fromClass(
                $property->getDeclaringClass()->getName(),
                $property->getDeclaringClass()->getFileName() ?: null,
            ));

            // Inherited defaults belong to the concrete child, which may supply the relation.
            $location = $property->getDeclaringClass()->getName() === $class->getName() ? $default : $node;
            $errors   = array_merge($errors, $this->relationExistenceHelper->check($type, new ObjectType($class->getName()), $location, $scope, $name === 'withCount'));
        }

        return $errors;
    }

    private function findProperty(ClassReflection $class, string $name): ReflectionProperty|null
    {
        $native = $class->getNativeReflection();

        if ($native->hasProperty($name) && $native->getProperty($name)->getDeclaringClass()->getName() === $class->getName()) {
            return $native->getProperty($name);
        }

        // BetterReflection can prefer a parent's property over a trait's overriding default.
        foreach ($class->getTraits() as $trait) {
            $property = $this->findProperty($trait, $name);

            if ($property !== null) {
                return $property;
            }
        }

        $parent = $class->getParentClass();

        return $parent === null ? null : $this->findProperty($parent, $name);
    }
}

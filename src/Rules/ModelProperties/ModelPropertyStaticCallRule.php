<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Rules\ModelProperties;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Expr\StaticCall>
 */
class ModelPropertyStaticCallRule implements Rule
{
    /** @var ReflectionProvider */
    private $reflectionProvider;

    /** @var ModelPropertiesRuleHelper */
    private $modelPropertiesRuleHelper;

    /** @var RuleLevelHelper */
    private $ruleLevelHelper;

    public function __construct(ReflectionProvider $reflectionProvider, ModelPropertiesRuleHelper $ruleHelper, RuleLevelHelper $ruleLevelHelper)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->modelPropertiesRuleHelper = $ruleHelper;
        $this->ruleLevelHelper = $ruleLevelHelper;
    }

    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    /**
     * @param  Node\Expr\StaticCall  $node
     * @param  Scope  $scope
     * @return string[]
     *
     * @throws \PHPStan\ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if (count($node->getArgs()) === 0) {
            return [];
        }

        $methodName = $node->name->name;

        $class = $node->class;

        if ($class instanceof Node\Name) {
            $className = (string) $class;
            $lowercasedClassName = strtolower($className);

            if (in_array($lowercasedClassName, ['self', 'static'], true)) {
                if (! $scope->isInClass()) {
                    return [];
                }

                $modelReflection = $scope->getClassReflection();
            } elseif ($lowercasedClassName === 'parent') {
                if (! $scope->isInClass()) {
                    return [];
                }

                $currentClassReflection = $scope->getClassReflection();

                $parentClass = $currentClassReflection->getParentClass();

                if ($parentClass === null) {
                    return [];
                }

                if ($scope->getFunctionName() === null) {
                    throw new \PHPStan\ShouldNotHappenException();
                }

                $modelReflection = $parentClass;
            } else {
                if (! $this->reflectionProvider->hasClass($className)) {
                    return [];
                }

                $modelReflection = $this->reflectionProvider->getClass($className);
            }
        } else {
            $classTypeResult = $this->ruleLevelHelper->findTypeToCheck(
                $scope,
                $class,
                '',
                static function (Type $type) use ($methodName): bool {
                    return $type->canCallMethods()->yes() && $type->hasMethod($methodName)->yes();
                }
            );

            $classType = $classTypeResult->getType();

            if ($classType instanceof ErrorType) {
                return [];
            }

            $strings = $classType->getConstantStrings();
            $classNames = $classType->getObjectClassNames();

            if (count($strings) === 1) {
                $modelClassName = $strings[0]->getValue();
            } elseif (count($classNames) === 1) {
                $modelClassName = $classNames[0];
            } else {
                return [];
            }

            $modelReflection = $this->reflectionProvider->getClass($modelClassName);
        }

        if (! $modelReflection->isSubclassOf(Model::class)) {
            return [];
        }

        if (! $modelReflection->hasMethod($methodName)) {
            return [];
        }

        $methodReflection = $modelReflection->getMethod($methodName, $scope);

        $className = $methodReflection->getDeclaringClass()->getName();

        if ($className !== Builder::class &&
            $className !== EloquentBuilder::class &&
            $className !== Relation::class &&
            $className !== Model::class
        ) {
            return [];
        }

        return $this->modelPropertiesRuleHelper->check($methodReflection, $scope, $node->getArgs(), $modelReflection);
    }
}

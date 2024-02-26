<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\ModelProperties;

use Larastan\Larastan\Rules\ModelRuleHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;

use function count;

/** @implements Rule<MethodCall> */
class ModelPropertyRule implements Rule
{
    private ModelPropertiesRuleHelper $modelPropertiesRuleHelper;

    public function __construct(ModelPropertiesRuleHelper $ruleHelper, private RuleLevelHelper $ruleLevelHelper, private ModelRuleHelper $modelRuleHelper)
    {
        $this->modelPropertiesRuleHelper = $ruleHelper;
    }

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param  MethodCall $node
     *
     * @return RuleError[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier) {
            return [];
        }

        if (count($node->getArgs()) === 0) {
            return [];
        }

        $name       = $node->name->name;
        $typeResult = $this->ruleLevelHelper->findTypeToCheck(
            $scope,
            $node->var,
            '',
            static function (Type $type) use ($name): bool {
                return $type->canCallMethods()->yes() && $type->hasMethod($name)->yes();
            },
        );

        $type = $typeResult->getType();

        if ($type instanceof ErrorType) {
            return [];
        }

        if (! $type->hasMethod($name)->yes()) {
            return [];
        }

        $modelReflection = $this->modelRuleHelper->findModelReflectionFromType($type);

        $methodReflection = $type->getMethod($name, $scope);

        return $this->modelPropertiesRuleHelper->check($methodReflection, $scope, $node->getArgs(), $modelReflection);
    }
}

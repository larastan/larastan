<?php

namespace NunoMaduro\Larastan\ReturnTypes;

use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class TranslatorHelper
{
    use HasContainer;

    public function resolveTypeFromCall(
        FunctionReflection|MethodReflection $reflection,
        FuncCall|MethodCall $call,
        Scope $scope
    ): Type {
        $trans = $this->getContainer()->get('translator');

        if ($trans === null) {
            return ParametersAcceptorSelector::selectSingle($reflection->getVariants())->getReturnType();
        }

        if (count($call->args) === 0 || $call->args[0] instanceof VariadicPlaceholder) {
            return ParametersAcceptorSelector::selectSingle($reflection->getVariants())->getReturnType();
        }

        $constantStrings = $scope->getType($call->args[0]->value)->getConstantStrings();

        if (count($constantStrings) === 0) {
            return ParametersAcceptorSelector::selectSingle($reflection->getVariants())->getReturnType();
        }

        $types = [];

        foreach ($constantStrings as $constantString) {
            $result = $trans->get($constantString->getValue());

            if (is_array($result)) {
                $types[] = new ArrayType(new MixedType(), new MixedType());
            }

            if (is_string($result)) {
                $types[] = new StringType();
            }
        }

        if (count($types) === 0) {
            return ParametersAcceptorSelector::selectSingle($reflection->getVariants())->getReturnType();
        }

        return count($types) === 1 ? $types[0] : TypeCombinator::union(...$types);
    }
}

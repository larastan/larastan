<?php

namespace NunoMaduro\Larastan\ReturnTypes;

use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ErrorType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;

final class AppMakeHelper
{
    use HasContainer;

    public function resolveTypeFromCall(FuncCall|MethodCall|StaticCall $call, Scope $scope): Type
    {
        $args = $call->getArgs();
        if (count($args) === 0) {
            return new ErrorType();
        }

        $argType = $scope->getType($args[0]->value);

        $constantStrings = $argType->getConstantStrings();

        if (count($constantStrings) > 0) {
            $types = [];
            foreach ($constantStrings as $constantString) {
                try {
                    /** @var object|null $resolved */
                    $resolved = $this->resolve($constantString->getValue());

                    if ($resolved === null) {
                        return new ErrorType();
                    }

                    $types[] = new ObjectType(get_class($resolved));
                } catch (Throwable $exception) {
                    return new ErrorType();
                }
            }

            return TypeCombinator::union(...$types);
        }

        return new MixedType();
    }
}

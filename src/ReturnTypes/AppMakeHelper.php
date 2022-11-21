<?php

namespace NunoMaduro\Larastan\ReturnTypes;

use NunoMaduro\Larastan\Concerns\HasContainer;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

final class AppMakeHelper
{
    use HasContainer;

    public function resolveTypeFromCall(FuncCall|MethodCall|StaticCall $call): Type
    {
        if (count($call->getArgs()) === 0) {
            return new ErrorType();
        }

        $expr = $call->getArgs()[0]->value;
        if ($expr instanceof String_) {
            try {
                /** @var object|null $resolved */
                $resolved = $this->resolve($expr->value);

                if ($resolved === null) {
                    return new ErrorType();
                }

                return new ObjectType(get_class($resolved));
            } catch (Throwable $exception) {
                return new ErrorType();
            }
        }

        if ($expr instanceof ClassConstFetch && $expr->class instanceof FullyQualified) {
            return new ObjectType($expr->class->toString());
        }

        return new NeverType();
    }
}

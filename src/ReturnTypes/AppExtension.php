<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Support\Facades\App;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

/**
 * @internal
 */
final class AppExtension implements DynamicStaticMethodReturnTypeExtension
{
    use Concerns\HasContainer;

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return App::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'make';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        /** @var Expr $expr */
        $expr = $methodCall->args[0]->value;

        if ($expr instanceof String_) {
            try {
                $resolved = $this->resolve($expr->value);

                if (is_null($resolved)) {
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

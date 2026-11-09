<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\Type\ExpressionTypeResolverExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

/**
 * Leaves the fields of a form request untyped while it reads them before validation has run.
 *
 * @internal
 */
#[Conditional(attribute: new AutowiredService(), if: '%checkFormRequestTypes%')]
final class FormRequestPropertyTypeExtension implements ExpressionTypeResolverExtension
{
    public function __construct(private FormRequestHelper $helper)
    {
    }

    public function getType(Expr $expr, Scope $scope): Type|null
    {
        $classReflection = $scope->getClassReflection();

        if (
            ! $expr instanceof PropertyFetch
            || ! $expr->name instanceof Identifier
            || $classReflection === null
            || ! $classReflection->is(FormRequest::class)
            || $classReflection->hasNativeProperty($expr->name->name)
            || ReflectionHelper::hasPropertyTag($classReflection, $expr->name->name)
            || $scope->hasExpressionType($expr)->yes()
            || ! $this->helper->isBeforeValidation($scope->getType($expr->var), $scope)
        ) {
            return null;
        }

        return new MixedType();
    }
}

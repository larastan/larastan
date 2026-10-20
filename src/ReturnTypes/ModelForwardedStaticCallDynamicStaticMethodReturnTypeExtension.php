<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Larastan\Larastan\Methods\BuilderHelper;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

use function count;

/**
 * Resolves `$class::findOrFail()` and other calls Model::__callStatic() forwards to the Eloquent
 * builder on a builder of the called model type, so a `class-string<T>` keeps T.
 */
final class ModelForwardedStaticCallDynamicStaticMethodReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function __construct(private BuilderHelper $builderHelper)
    {
    }

    public function getClass(): string
    {
        return Model::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->is(EloquentBuilder::class);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type|null {
        if ($methodCall->class instanceof Name || $methodCall->isFirstClassCallable()) {
            return null;
        }

        $modelType = $scope->getType($methodCall->class)->getObjectTypeOrClassStringObjectType();

        // A concrete model class already resolves through ModelForwardsCallsExtension,
        // a template type is replaced by its bound there.
        if (! TypeUtils::containsTemplateType($modelType)) {
            return null;
        }

        $modelClassNames = $modelType->getObjectClassNames();

        if (count($modelClassNames) !== 1) {
            return null;
        }

        $builderType = $this->builderHelper->determineBuilderClass($modelClassNames[0], $modelType);

        if ($builderType === null) {
            return null;
        }

        return $scope->getType(new MethodCall(
            new TypeExpr($builderType),
            $methodCall->name,
            $methodCall->getArgs(),
        ));
    }
}

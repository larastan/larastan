<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\DataAccessorHelper;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function count;

final class FormRequestValidatedDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(private FormRequestHelper $formRequestHelper)
    {
    }

    public function getClass(): string
    {
        return FormRequest::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'validated';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        if ($methodReflection->getDeclaringClass()->getName() !== FormRequest::class) {
            return null;
        }

        $args              = $methodCall->getArgs();
        $validatedDataType = $this->formRequestHelper->getValidatedDataType($scope->getType($methodCall->var), 'validated', $scope);

        if ($validatedDataType === null || count($args) === 0) {
            return $validatedDataType;
        }

        $keyType = $scope->getType($args[0]->value);

        if ($keyType->isNull()->yes()) {
            return $validatedDataType;
        }

        $segments = DataAccessorHelper::parseKey($keyType);

        if ($segments === null) {
            return null;
        }

        $defaultType = count($args) > 1
            ? DataAccessorHelper::resolveDefaultType($scope->getType($args[1]->value), $scope)
            : new NullType();

        [$selectedType, $fallsBack] = DataAccessorHelper::select($validatedDataType, $segments);

        return $fallsBack ? TypeCombinator::union($selectedType, $defaultType) : $selectedType;
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\DataAccessorTypeResolver;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

final class FormRequestValidatedDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private FormRequestHelper $formRequestHelper,
        private DataAccessorTypeResolver $dataAccessorTypeResolver,
    ) {
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

        $validatedDataType = $this->formRequestHelper->getValidatedDataType($scope->getType($methodCall->var), 'validated', $scope);

        return $validatedDataType === null ? null : $this->dataAccessorTypeResolver->resolveAccessor(
            'input',
            [$validatedDataType],
            $methodCall->getArgs(),
            $scope,
            false,
        );
    }
}

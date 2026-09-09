<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Larastan\Larastan\Support\DataAccessorHelper;
use Larastan\Larastan\Support\FormRequestHelper;
use Larastan\Larastan\Support\FormRequestLifecycle;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function in_array;
use function strtolower;

/** Narrows the input accessors of a FormRequest, such as input() and integer(), to its validation rules. */
final class FormRequestInputDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const METHODS = [
        'all',
        'array',
        'boolean',
        'collect',
        'enum',
        'enums',
        'except',
        'exists',
        'float',
        'has',
        'input',
        'integer',
        'missing',
        'only',
    ];

    public function __construct(
        private FormRequestHelper $formRequestHelper,
        private FormRequestLifecycle $lifecycle,
        private DataAccessorHelper $dataAccessorHelper,
    ) {
    }

    public function getClass(): string
    {
        return FormRequest::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(strtolower($methodReflection->getName()), self::METHODS, true);
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        if ($methodReflection->getDeclaringClass()->getName() !== Request::class) {
            return null;
        }

        $requestType = $scope->getType($methodCall->var);

        if ($this->lifecycle->isBeforeValidation($scope, $requestType)) {
            return null;
        }

        $dataTypes = $this->formRequestHelper->getInputDataTypes($requestType, $methodReflection->getName(), $scope);

        if ($dataTypes === null) {
            return null;
        }

        // input() reads the request body and query string, so uploaded files are not among its values.
        return $this->dataAccessorHelper->resolveAccessor($methodReflection->getName(), $dataTypes, $methodCall->getArgs(), $scope, true);
    }
}

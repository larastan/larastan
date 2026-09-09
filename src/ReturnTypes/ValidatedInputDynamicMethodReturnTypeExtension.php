<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\ValidatedInput;
use Larastan\Larastan\Support\DataAccessorHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

use function in_array;
use function strtolower;

/** Narrows the accessors of a ValidatedInput, such as input() and integer(), to the validated array shape it carries. */
final class ValidatedInputDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
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

    public function __construct(private DataAccessorHelper $dataAccessorHelper)
    {
    }

    public function getClass(): string
    {
        return ValidatedInput::class;
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
        if ($methodReflection->getDeclaringClass()->getName() !== ValidatedInput::class) {
            return null;
        }

        $dataType = $scope->getType($methodCall->var)->getTemplateType(ValidatedInput::class, 'TData');

        if (! $dataType->isConstantArray()->yes()) {
            return null;
        }

        return $this->dataAccessorHelper->resolveAccessor(
            $methodReflection->getName(),
            $dataType->getConstantArrays(),
            $methodCall->getArgs(),
            $scope,
            false,
        );
    }
}

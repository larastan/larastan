<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\ValidatedInput;
use Larastan\Larastan\Support\DataAccessorTypeResolver;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;

use function count;

final class FormRequestSafeDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function __construct(
        private FormRequestHelper $formRequestHelper,
        private bool $checkFormRequestTypes,
    ) {
    }

    public function getClass(): string
    {
        return FormRequest::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'safe';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        if (! $this->checkFormRequestTypes) {
            return $this->getLegacyType($methodCall, $scope);
        }

        if ($methodReflection->getDeclaringClass()->getName() !== FormRequest::class) {
            return null;
        }

        $validatedDataType = $this->formRequestHelper->getValidatedDataType($scope->getType($methodCall->var), 'safe', $scope);

        if ($validatedDataType === null) {
            return null;
        }

        $args = $methodCall->getArgs();

        if (count($args) === 0 || $scope->getType($args[0]->value)->isNull()->yes()) {
            return new GenericObjectType(ValidatedInput::class, [$validatedDataType]);
        }

        $argType = $scope->getType($args[0]->value);

        if (! $argType->isConstantArray()->yes()) {
            return null;
        }

        $paths = DataAccessorTypeResolver::parsePaths([$argType]);

        return $paths === null ? null : DataAccessorTypeResolver::selectPaths($validatedDataType, $paths);
    }

    private function getLegacyType(MethodCall $methodCall, Scope $scope): Type|null
    {
        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return null;
        }

        $constantArrays = $scope->getType($args[0]->value)->getConstantArrays();

        if (count($constantArrays) !== 1) {
            return null;
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($constantArrays[0]->getValueTypes() as $keyType) {
            foreach ($keyType->getConstantStrings() as $constantString) {
                $builder->setOffsetValueType($constantString, new MixedType());
            }
        }

        return $builder->getArray();
    }
}

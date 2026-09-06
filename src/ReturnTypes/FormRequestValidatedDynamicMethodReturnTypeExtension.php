<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;

use function array_intersect;
use function array_map;
use function array_shift;
use function count;
use function explode;

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

        $constantScalars = $keyType->getConstantScalarTypes();

        if (count($constantScalars) !== 1) {
            return null;
        }

        $key             = $constantScalars[0];
        $constantStrings = $key->getConstantStrings();

        if (count($constantStrings) === 1) {
            $segments = explode('.', $constantStrings[0]->getValue());

            if (array_intersect($segments, ['*', '{first}', '{last}', '\\*', '\\{first}', '\\{last}']) !== []) {
                return null;
            }

            $segments = array_map(static fn (string $segment): Type => new ConstantStringType($segment), $segments);
        } elseif ($key->isInteger()->yes()) {
            $segments = [$key];
        } else {
            return null;
        }

        $defaultType = count($args) > 1
            ? $this->resolveDefaultType($scope->getType($args[1]->value), $scope)
            : new NullType();

        return $this->select($validatedDataType, $segments, $defaultType);
    }

    /** @param list<Type> $segments */
    private function select(Type $type, array $segments, Type $defaultType): Type
    {
        if ($segments === []) {
            return $type;
        }

        $segment = array_shift($segments);
        $hasKey  = $type->hasOffsetValueType($segment);

        if ($hasKey->no()) {
            return $defaultType;
        }

        $selectedType = $this->select($type->getOffsetValueType($segment), $segments, $defaultType);

        return $hasKey->yes() ? $selectedType : TypeCombinator::union($selectedType, $defaultType);
    }

    private function resolveDefaultType(Type $type, Scope $scope): Type
    {
        return TypeTraverser::map($type, static function (Type $type, callable $traverse) use ($scope): Type {
            if ($type instanceof UnionType) {
                return $traverse($type);
            }

            $isClosure = (new ObjectType(Closure::class))->isSuperTypeOf($type);

            if ($isClosure->yes()) {
                return $type->getCallableParametersAcceptors($scope)[0]->getReturnType();
            }

            return $isClosure->maybe() ? new MixedType() : $type;
        });
    }
}

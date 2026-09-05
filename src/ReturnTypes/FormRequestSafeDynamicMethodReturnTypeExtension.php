<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\ValidatedInput;
use Larastan\Larastan\Support\FormRequestHelper;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function array_slice;
use function count;
use function explode;

final class FormRequestSafeDynamicMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
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
        return $methodReflection->getName() === 'safe';
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope,
    ): Type|null {
        if ($methodReflection->getDeclaringClass()->getName() !== FormRequest::class) {
            return null;
        }

        $validatedDataType = $this->getValidatedDataType($methodCall, $scope);

        if ($validatedDataType === null) {
            return null;
        }

        $args = $methodCall->getArgs();

        if (count($args) === 0) {
            return new GenericObjectType(ValidatedInput::class, [$validatedDataType]);
        }

        $argType = $scope->getType($args[0]->value);

        $constantArrays = $argType->getConstantArrays();

        if (count($constantArrays) !== 1) {
            return null;
        }

        $paths = [];

        foreach ($constantArrays[0]->getValueTypes() as $keyType) {
            $constantStrings = $keyType->getConstantStrings();

            if (count($constantStrings) !== 1) {
                return null;
            }

            $paths[] = explode('.', $constantStrings[0]->getValue());
        }

        return $this->select($validatedDataType, $paths);
    }

    private function getValidatedDataType(MethodCall $methodCall, Scope $scope): Type|null
    {
        $classReflections = $scope->getType($methodCall->var)->getObjectClassReflections();
        $types            = [];

        if ($classReflections === []) {
            return null;
        }

        foreach ($classReflections as $classReflection) {
            if (! $classReflection->is(FormRequest::class)) {
                return null;
            }

            $type = $this->formRequestHelper->getValidatedDataType($classReflection);

            if ($type === null) {
                return null;
            }

            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }

    /** @param list<list<string>> $paths */
    private function select(Type $type, array $paths): Type|null
    {
        $arrayType = TypeCombinator::removeNull($type);

        if (! $arrayType->isConstantArray()->yes()) {
            return null;
        }

        $selected = [];

        foreach ($arrayType->getConstantArrays() as $constantArray) {
            $selectedType = $this->selectFromArray($constantArray, $paths);

            if ($selectedType === null) {
                return null;
            }

            $selected[] = $selectedType;
        }

        return TypeCombinator::union(...$selected);
    }

    /** @param list<list<string>> $paths */
    private function selectFromArray(ConstantArrayType $type, array $paths): Type|null
    {
        /** @var array<string, list<list<string>>|true> $pathsBySegment */
        $pathsBySegment = [];

        foreach ($paths as $path) {
            if ($path === []) {
                continue;
            }

            $segment = $path[0];

            if (count($path) === 1) {
                $pathsBySegment[$segment] = true;

                continue;
            }

            $segmentPaths = $pathsBySegment[$segment] ?? [];

            if ($segmentPaths === true) {
                continue;
            }

            $segmentPaths[] = array_slice($path, 1);

            $pathsBySegment[$segment] = $segmentPaths;
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($pathsBySegment as $segment => $children) {
            $keyType = new ConstantStringType($segment);
            $hasKey  = $type->hasOffsetValueType($keyType);

            if ($hasKey->no()) {
                continue;
            }

            $valueType = $type->getOffsetValueType($keyType);

            if ($children === true) {
                $builder->setOffsetValueType($keyType, $valueType, ! $hasKey->yes());

                continue;
            }

            $selected = $this->select($valueType, $children);

            if ($selected === null) {
                return null;
            }

            if ($selected->isIterableAtLeastOnce()->no()) {
                continue;
            }

            $builder->setOffsetValueType(
                $keyType,
                $selected,
                ! $hasKey->yes() || ! $valueType->isConstantArray()->yes()
                    || ! $selected->isIterableAtLeastOnce()->yes(),
            );
        }

        return $builder->getArray();
    }
}

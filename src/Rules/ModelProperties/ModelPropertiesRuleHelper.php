<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\ModelProperties;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Larastan\Larastan\Types\ModelProperty\GenericModelPropertyType;
use Larastan\Larastan\Types\ModelProperty\ModelPropertyType;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;

use function array_key_exists;
use function array_merge;
use function count;
use function mb_strpos;
use function sprintf;

class ModelPropertiesRuleHelper
{
    /**
     * @param  Node\Arg[] $args
     *
     * @return RuleError[]
     *
     * @throws ShouldNotHappenException
     */
    public function check(MethodReflection $methodReflection, Scope $scope, array $args, ClassReflection|null $modelReflection = null): array
    {
        $modelPropertyParameter = $this->hasModelPropertyParameter($methodReflection, $scope, $args, $modelReflection);

        if (count($modelPropertyParameter) !== 2) {
            return [];
        }

        /** @var int $parameterIndex */
        /** @var Type $modelType */
        [$parameterIndex, $modelType] = $modelPropertyParameter;

        if (! (new ObjectType(Model::class))->isSuperTypeOf($modelType)->yes() || $modelType->equals(new ObjectType(Model::class))) {
            return [];
        }

        if (! array_key_exists($parameterIndex, $args)) {
            return [];
        }

        $argValue = $args[$parameterIndex]->value;

        $argType = $scope->getType($argValue);

        if ($argType->isConstantArray()->yes()) {
            $errors = [];

            $constantArrays = $argType->getConstantArrays();

            $valueTypes = [];

            foreach ($constantArrays as $constantArray) {
                $keyType = $constantArray->getKeyType()->generalize(GeneralizePrecision::lessSpecific());

                if ($keyType->isInteger()->yes()) {
                    $valueTypes = array_merge($valueTypes, $constantArray->getValuesArray()->getValueTypes());
                } elseif ($keyType->isString()->yes()) {
                    $valueTypes = array_merge($valueTypes, $constantArray->getKeysArray()->getValueTypes());
                }
            }

            foreach ($valueTypes as $valueType) {
                $strings = $valueType->getConstantStrings();

                // It could be something like `DB::raw`
                // We only want to analyze strings
                if ($strings === []) {
                    continue;
                }

                foreach ($strings as $string) {
                    // TODO: maybe check table names and columns here. And for JSON access maybe just the column name
                    if (mb_strpos($string->getValue(), '.') !== false || mb_strpos($string->getValue(), '->') !== false) {
                        continue;
                    }

                    if ($modelType->hasProperty($string->getValue())->yes()) {
                        continue;
                    }

                    $error = sprintf('Property \'%s\' does not exist in %s model.', $string->getValue(), $modelType->describe(VerbosityLevel::typeOnly()));

                    if ($methodReflection->getDeclaringClass()->getName() === BelongsToMany::class) {
                        $error .= sprintf(" If '%s' exists as a column on the pivot table, consider using 'wherePivot' or prefix the column with table name instead.", $string->getValue());
                    }

                    $errors[] = RuleErrorBuilder::message($error)
                        ->identifier('larastan.modelProperty.notFound')
                        ->build();
                }
            }

            return $errors;
        }

        $argStrings = $argType->getConstantStrings();

        if ($argStrings === []) {
            return [];
        }

        foreach ($argStrings as $argString) {
            // TODO: maybe check table names and columns here. And for JSON access maybe just the column name
            if (mb_strpos($argString->getValue(), '.') !== false || mb_strpos($argString->getValue(), '->') !== false) {
                return [];
            }

            if ($modelType->hasProperty($argString->getValue())->yes()) {
                continue;
            }

            $error = sprintf('Property \'%s\' does not exist in %s model.', $argString->getValue(), $modelType->describe(VerbosityLevel::typeOnly()));

            if ((new ObjectType(BelongsToMany::class))->isSuperTypeOf(ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType())->yes()) {
                $error .= sprintf(" If '%s' exists as a column on the pivot table, consider using 'wherePivot' or prefix the column with table name instead.", $argString->getValue());
            }

            return [
                RuleErrorBuilder::message($error)
                ->identifier('larastan.modelProperty.notFound')
                ->build(),
            ];
        }

        return [];
    }

    /**
     * @param  Node\Arg[] $args
     *
     * @return array<int, int|Type>
     */
    public function hasModelPropertyParameter(
        MethodReflection $methodReflection,
        Scope $scope,
        array $args,
        ClassReflection|null $modelReflection = null,
    ): array {
        $parameters = ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants())->getParameters();

        foreach ($parameters as $index => $parameter) {
            $type = $parameter->getType();

            if ($type instanceof UnionType) {
                foreach ($type->getTypes() as $innerType) {
                    if ($innerType instanceof GenericModelPropertyType) {
                        return [$index, $innerType->getGenericType()];
                    }

                    if ($innerType instanceof ModelPropertyType && $modelReflection !== null) {
                        return [$index, new ObjectType($modelReflection->getName())];
                    }
                }
            } elseif ($type->isArray()->yes()) {
                $keyType  = $type->getIterableKeyType();
                $itemType = $type->getIterableValueType();

                if ($keyType instanceof GenericModelPropertyType) {
                    return [$index, $keyType->getGenericType()];
                }

                if ($itemType instanceof GenericModelPropertyType) {
                    return [$index, $itemType->getGenericType()];
                }

                if ($modelReflection !== null && (($keyType instanceof ModelPropertyType) || ($itemType instanceof ModelPropertyType))) {
                    return [$index, new ObjectType($modelReflection->getName())];
                }
            } else {
                if ($type instanceof GenericModelPropertyType) {
                    return [$index, $type->getGenericType()];
                }

                if ($modelReflection !== null && $type instanceof ModelPropertyType) {
                    return [$index, new ObjectType($modelReflection->getName())];
                }
            }
        }

        return [];
    }
}

<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\Enum\EnumCaseObjectType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;

use function array_intersect;
use function array_key_first;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function in_array;
use function is_int;
use function is_numeric;
use function is_string;
use function strtolower;

/**
 * Models the reads Laravel's InteractsWithData trait performs on request data.
 *
 * Every accessor is expressed over an array shape: data_get() selection for
 * keyed reads, followed by the cast the accessor applies.
 *
 * @internal
 */
final class DataAccessorTypeResolver
{
    /** Laravel resolves these segments against several keys at once, so no single value type applies. */
    private const SELECTORS = ['*', '{first}', '{last}', '\\*', '\\{first}', '\\{last}'];

    /** Accessors reading one value through data(), which Request::input() serves without uploaded files. */
    private const VALUE_METHODS = ['input', 'integer', 'float', 'boolean', 'enum', 'enums', 'array', 'collect'];

    public function __construct(private CollectionHelper $collectionHelper)
    {
    }

    /**
     * Resolve each request shape separately before combining the accessor results.
     *
     * @param list<Type> $dataTypes
     * @param array<Arg> $args
     */
    public function resolveAccessor(string $method, array $dataTypes, array $args, Scope $scope, bool $excludesUploadedFiles): Type|null
    {
        $method        = strtolower($method);
        $argumentTypes = array_map(static fn (Arg $arg): Type => $scope->getType($arg->value), $args);

        if ($method === 'all') {
            return $args === [] ? TypeCombinator::union(...$dataTypes) : null;
        }

        $types = [];

        foreach ($dataTypes as $dataType) {
            $type = in_array($method, self::VALUE_METHODS, true)
                ? $this->resolveValue($method, $dataType, $argumentTypes, $scope, $excludesUploadedFiles)
                : self::resolveKeys($method, $dataType, $argumentTypes);

            if ($type === null) {
                return null;
            }

            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }

    /** @param array<Type> $args */
    private function resolveValue(string $method, Type $dataType, array $args, Scope $scope, bool $excludesUploadedFiles): Type|null
    {
        $segments = $args === [] || $args[0]->isNull()->yes() ? [] : self::parseKey($args[0]);

        // Only input(), array(), and collect() accept the whole array.
        if ($segments === null || ($segments === [] && ! in_array($method, ['input', 'array', 'collect'], true))) {
            return null;
        }

        [$valueType, $presence] = self::select($dataType, $segments);

        if ($excludesUploadedFiles && self::mayContainUploadedFile($valueType)) {
            return null;
        }

        $enumCases = in_array($method, ['enum', 'enums'], true) && count($args) > 1
            ? self::enumCases($args[1]->getClassStringObjectType())
            : null;

        $defaultArg  = $args[$method === 'enum' ? 2 : 1] ?? null;
        $defaultType = match (true) {
            $defaultArg !== null && $method !== 'enums' => self::resolveDefaultType($defaultArg, $scope),
            $method === 'integer' => new ConstantIntegerType(0),
            $method === 'float' => new ConstantFloatType(0.0),
            $method === 'boolean' => new ConstantBooleanType(false),
            default => new NullType(),
        };

        // Numeric validation shares the integer bounds across float and numeric-string representations.
        // Apply this only to the validated value: defaults have no such guarantee.
        if ($method === 'integer' && $valueType instanceof UnionType && ! $valueType->isInteger()->no()) {
            $valueType = TypeUtils::toStrictUnion($valueType)->filterTypes(
                static fn (Type $type): bool => $type->isConstantScalarValue()->yes()
                    || (! $type->isFloat()->yes() && ! $type->isNumericString()->yes()),
            );
        }

        $type         = $this->cast($method, $valueType, $enumCases, $defaultType);
        $fallbackType = ! $presence->yes() ? $this->cast($method, $defaultType, $enumCases, $defaultType, absent: true) : new NeverType();

        return $type === null || $fallbackType === null ? null : TypeCombinator::union($type, $fallbackType);
    }

    /** @param array<Type> $args */
    private static function resolveKeys(string $method, Type $dataType, array $args): Type|null
    {
        $paths = self::parsePaths($args);

        if ($paths === null || $paths === []) {
            return null;
        }

        if ($method === 'only') {
            return self::selectPaths($dataType, $paths);
        }

        if ($method === 'except') {
            if (! $dataType->isConstantArray()->yes()) {
                return null;
            }

            foreach ($paths as $path) {
                if (count($path) !== 1) {
                    return null; // Nested Arr::forget paths need different handling.
                }

                $dataType = $dataType->unsetOffset(ConstantTypeHelper::getTypeFromValue($path[0]));
            }

            return $dataType;
        }

        $present = TrinaryLogic::createYes();

        foreach ($paths as $path) {
            [$valueType, $presence] = self::select($dataType, $path);
            $present                = $present->and($valueType instanceof NeverType ? TrinaryLogic::createNo() : $presence);
        }

        return ($method === 'missing' ? $present->negate() : $present)->toBooleanType();
    }

    /** @return list<int|string>|null The dot-separated segments of an exact key, or null for dynamic keys and selectors. */
    private static function parseKey(Type $keyType): array|null
    {
        $values = $keyType->getConstantScalarValues();

        if (count($values) !== 1 || (! is_int($values[0]) && ! is_string($values[0]))) {
            return null;
        }

        $key      = $values[0];
        $segments = is_int($key) ? [$key] : explode('.', $key);

        return array_intersect($segments, self::SELECTORS) === [] ? $segments : null;
    }

    /**
     * @param array<Type> $keyTypes
     *
     * @return list<list<int|string>>|null
     */
    public static function parsePaths(array $keyTypes): array|null
    {
        if (count($keyTypes) === 1 && $keyTypes[0]->isArray()->yes()) {
            $constantArrays = $keyTypes[0]->getConstantArrays();

            if (count($constantArrays) !== 1 || $constantArrays[0]->getOptionalKeys() !== []) {
                return null;
            }

            $keyTypes = $constantArrays[0]->getValueTypes();
        }

        $paths = [];

        foreach ($keyTypes as $keyType) {
            $segments = $keyType->isString()->yes() ? self::parseKey($keyType) : null;

            if ($segments === null) {
                return null;
            }

            $paths[] = $segments;
        }

        return $paths;
    }

    /**
     * @param list<int|string> $segments
     *
     * @return array{Type, TrinaryLogic} The data_get() value and whether the path is present.
     */
    private static function select(Type $type, array $segments): array
    {
        $presence = TrinaryLogic::createYes();

        foreach ($segments as $segment) {
            $keyType = ConstantTypeHelper::getTypeFromValue($segment);
            $hasKey  = $type->hasOffsetValueType($keyType);

            if ($hasKey->no()) {
                return [new NeverType(), $hasKey];
            }

            $presence = $presence->and($hasKey);
            $type     = $type->getOffsetValueType($keyType);
        }

        return [$type, $presence];
    }

    /** @param list<list<int|string>> $paths */
    public static function selectPaths(Type $type, array $paths): Type|null
    {
        $arrayType = TypeCombinator::removeNull($type);

        if (! $arrayType->isConstantArray()->yes()) {
            return null;
        }

        // A selected parent includes all its children, irrespective of path order.
        /** @var array<int|string, list<list<int|string>>> $childrenByKey */
        $childrenByKey = [];

        foreach ($paths as $path) {
            if ($path === []) {
                continue;
            }

            $childrenByKey[$path[0]][] = array_slice($path, 1);
        }

        $results = [];

        foreach ($arrayType->getConstantArrays() as $array) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($childrenByKey as $key => $children) {
                $keyType = ConstantTypeHelper::getTypeFromValue($key);
                $hasKey  = $array->hasOffsetValueType($keyType);

                if ($hasKey->no()) {
                    continue;
                }

                $value    = $array->getOffsetValueType($keyType);
                $optional = ! $hasKey->yes();

                if (! in_array([], $children, true)) {
                    $selected = self::selectPaths($value, $children);

                    if ($selected === null) {
                        return null;
                    }

                    if ($selected->isIterableAtLeastOnce()->no()) {
                        continue;
                    }

                    $optional = $optional || ! $value->isConstantArray()->yes() || ! $selected->isIterableAtLeastOnce()->yes();
                    $value    = $selected;
                }

                $builder->setOffsetValueType($keyType, $value, $optional);
            }

            $results[] = $builder->getArray();
        }

        return TypeCombinator::union(...$results);
    }

    /** The value data_get() produces from a default, which it invokes when it is a Closure. */
    private static function resolveDefaultType(Type $type, Scope $scope): Type
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

    /** @param array<int|string, EnumCaseObjectType>|null $enumCases */
    private function cast(string $method, Type $type, array|null $enumCases, Type $defaultType, bool $absent = false): Type|null
    {
        if ($type instanceof NeverType) {
            return $type;
        }

        return match ($method) {
            'integer' => $type->toInteger() instanceof ErrorType ? null : $type->toInteger(),
            'float' => $type->toFloat() instanceof ErrorType ? null : $type->toFloat(),
            'boolean' => self::castToBoolean($type),
            'enum' => $absent ? $defaultType : ($enumCases === null ? null : self::castToEnum($type, $enumCases, $defaultType)),
            'enums' => $absent ? new ConstantArrayType([], []) : ($enumCases === null ? null : self::castToEnums($type, $enumCases)),
            'array' => self::castToArray($type),
            'collect' => $this->castToCollection($type),
            default => $type,
        };
    }

    /** FILTER_VALIDATE_BOOLEAN differs from a PHP boolean cast. */
    private static function castToBoolean(Type $type): Type
    {
        foreach ([true => [true, 1, '1', 'true', 'on', 'yes'], false => [false, 0, '0', 'false', 'off', 'no', '', null]] as $result => $values) {
            $accepted = TypeCombinator::union(...array_map(ConstantTypeHelper::getTypeFromValue(...), $values));

            if ($accepted->isSuperTypeOf($type)->yes()) {
                return new ConstantBooleanType((bool) $result);
            }
        }

        return new BooleanType();
    }

    /** @param array<int|string, EnumCaseObjectType> $cases */
    private static function castToEnum(Type $type, array $cases, Type $defaultType): Type
    {
        // A unit enum has no tryFrom(), so the default is returned.
        if ($cases === []) {
            return $defaultType;
        }

        if (! $type->isConstantScalarValue()->yes()) {
            return TypeCombinator::union($defaultType, ...array_values($cases));
        }

        return TypeCombinator::union(...array_map(
            static fn (bool|float|int|string|null $value): Type => self::matchCase($cases, $value) ?? $defaultType,
            $type->getConstantScalarValues(),
        ));
    }

    /** @param array<int|string, EnumCaseObjectType> $cases */
    private static function castToEnums(Type $type, array $cases): Type|null
    {
        $arrayType = $cases === [] ? new ConstantArrayType([], []) : self::castToArray($type);

        if ($arrayType === null) {
            return null;
        }

        $caseType = TypeCombinator::removeNull(self::castToEnum($arrayType->getIterableValueType(), $cases, new NullType()));

        // filter() drops values without a case and preserves the remaining keys.
        return $caseType instanceof NeverType ? new ConstantArrayType([], []) : new ArrayType($arrayType->getIterableKeyType(), $caseType);
    }

    /** Objects and mixed need the accessor's declared fallback, rather than PHP's object-to-array cast. */
    private static function castToArray(Type $type): Type|null
    {
        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $member) {
            if (! $member->isArray()->yes() && ! $member->isScalar()->yes() && ! $member->isNull()->yes()) {
                return null;
            }
        }

        return $type->toArray();
    }

    private function castToCollection(Type $type): Type|null
    {
        $arrayType = self::castToArray($type);

        if ($arrayType === null) {
            return null;
        }

        return $this->collectionHelper->determineGenericCollectionTypeFromType($arrayType)
            ?? new GenericObjectType(Collection::class, [$arrayType->getIterableKeyType(), $arrayType->getIterableValueType()]);
    }

    /** @return array<int|string, EnumCaseObjectType>|null */
    private static function enumCases(Type $enumType): array|null
    {
        $cases = $enumType->getEnumCases();

        if ($cases === []) {
            return null;
        }

        $result = [];

        foreach ($cases as $case) {
            $backingType = $case->getBackingValueType();

            if ($backingType === null) {
                return [];
            }

            foreach ([...TypeUtils::getConstantIntegers($backingType), ...$backingType->getConstantStrings()] as $value) {
                $result[$value->getValue()] = new EnumCaseObjectType($case->getClassName(), $case->getEnumCaseName());
            }
        }

        return $result;
    }

    /** @param non-empty-array<int|string, EnumCaseObjectType> $cases */
    private static function matchCase(array $cases, bool|float|int|string|null $value): EnumCaseObjectType|null
    {
        $isIntegerBacked = is_int(array_key_first($cases));

        if ($isIntegerBacked && is_string($value) && ! is_numeric($value)) {
            return null;
        }

        return $cases[$isIntegerBacked ? (int) $value : (string) $value] ?? null;
    }

    private static function mayContainUploadedFile(Type $type): bool
    {
        $found = false;
        TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$found): Type {
            $found = $found || (! $type instanceof MixedType && ! (new ObjectType(UploadedFile::class))->isSuperTypeOf($type)->no());

            return ! $found && ($type->isArray()->yes() || $type instanceof UnionType) ? $traverse($type) : $type;
        });

        return $found;
    }
}

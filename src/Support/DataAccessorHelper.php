<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
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
use function trim;

/**
 * Models the reads Laravel's InteractsWithData trait performs on request data.
 *
 * Every accessor is expressed over an array shape: data_get() selection for
 * keyed reads, followed by the cast the accessor applies.
 *
 * @internal
 */
final class DataAccessorHelper
{
    /** Laravel resolves these segments against several keys at once, so no single value type applies. */
    private const SELECTORS = ['*', '{first}', '{last}', '\\*', '\\{first}', '\\{last}'];

    /** Accessors reading one value through data(), which Request::input() serves without uploaded files. */
    private const VALUE_METHODS = ['input', 'integer', 'float', 'boolean', 'enum', 'enums', 'array', 'collect'];

    public function __construct(private CollectionHelper $collectionHelper)
    {
    }

    /**
     * The return type of $method called with $args on data of the given shapes.
     *
     * Each request class the receiver may be contributes its own array shape, kept
     * apart so that every cast sees the bounds of its own rules. Request::input()
     * omits uploaded files, which $excludesUploadedFiles declares.
     *
     * @param list<Type> $dataTypes
     * @param array<Arg> $args
     */
    public function resolveAccessor(string $method, array $dataTypes, array $args, Scope $scope, bool $excludesUploadedFiles): Type|null
    {
        $method = strtolower($method);

        if ($method === 'all') {
            return $args === [] ? TypeCombinator::union(...$dataTypes) : null;
        }

        $types = [];

        foreach ($dataTypes as $dataType) {
            $type = in_array($method, self::VALUE_METHODS, true)
                ? $this->resolveValue($method, $dataType, $args, $scope, $excludesUploadedFiles)
                : self::resolveKeys($method, $dataType, $args, $scope);

            if ($type === null) {
                return null;
            }

            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }

    /** @param array<Arg> $args */
    private function resolveValue(string $method, Type $dataType, array $args, Scope $scope, bool $excludesUploadedFiles): Type|null
    {
        $segments = $args === [] || $scope->getType($args[0]->value)->isNull()->yes() ? [] : self::parseKey($scope->getType($args[0]->value));

        // Only input(), array(), and collect() accept the whole array.
        if ($segments === null || ($segments === [] && ! in_array($method, ['input', 'array', 'collect'], true))) {
            return null;
        }

        [$valueType, $fallsBack] = self::select($dataType, $segments);

        if ($excludesUploadedFiles && self::mayContainUploadedFile($valueType)) {
            return null;
        }

        $enumType = in_array($method, ['enum', 'enums'], true) && count($args) > 1
            ? $scope->getType($args[1]->value)->getClassStringObjectType()
            : null;

        $defaultArg  = $args[$method === 'enum' ? 2 : 1] ?? null;
        $defaultType = match (true) {
            $defaultArg !== null && $method !== 'enums' => self::resolveDefaultType($scope->getType($defaultArg->value), $scope),
            $method === 'integer' => new ConstantIntegerType(0),
            $method === 'float' => new ConstantFloatType(0.0),
            $method === 'boolean' => new ConstantBooleanType(false),
            default => new NullType(),
        };

        // The default is cast on its own: its integer members must not stand in for the value's bounds.
        $type         = $this->cast($method, $valueType, $enumType, $defaultType);
        $fallbackType = $fallsBack ? $this->cast($method, $defaultType, $enumType, $defaultType, absent: true) : new NeverType();

        return $type === null || $fallbackType === null ? null : TypeCombinator::union($type, $fallbackType);
    }

    /** @param array<Arg> $args */
    private static function resolveKeys(string $method, Type $dataType, array $args, Scope $scope): Type|null
    {
        $paths = self::parsePaths($args, $scope);

        if ($paths === null) {
            return null;
        }

        if ($method === 'only') {
            return self::selectPaths($dataType, $paths);
        }

        if ($method === 'except') {
            return self::forgetKeys($dataType, $paths);
        }

        $present = true;

        foreach ($paths as $path) {
            [$valueType, $fallsBack] = self::select($dataType, array_map(static fn (string $segment): Type => new ConstantStringType($segment), $path));

            if ($valueType instanceof NeverType) {
                $present = false;

                break;
            }

            $present = $fallsBack ? null : $present;
        }

        if ($present === null) {
            return TypeCombinator::union(new ConstantBooleanType(true), new ConstantBooleanType(false));
        }

        return new ConstantBooleanType($method === 'missing' ? ! $present : $present);
    }

    /** @return list<Type>|null The dot-separated segments of an exact key, or null for dynamic keys and selectors. */
    public static function parseKey(Type $keyType): array|null
    {
        $constantScalars = $keyType->getConstantScalarTypes();

        if (count($constantScalars) !== 1) {
            return null;
        }

        $key             = $constantScalars[0];
        $constantStrings = $key->getConstantStrings();

        if (count($constantStrings) === 1) {
            $segments = explode('.', $constantStrings[0]->getValue());

            if (array_intersect($segments, self::SELECTORS) !== []) {
                return null;
            }

            return array_map(static fn (string $segment): Type => new ConstantStringType($segment), $segments);
        }

        return $key->isInteger()->yes() ? [$key] : null;
    }

    /**
     * The exact key paths of an array argument or of variadic string arguments, or null when any is dynamic.
     *
     * @param array<Arg> $args
     *
     * @return list<list<string>>|null
     */
    public static function parsePaths(array $args, Scope $scope): array|null
    {
        $keyTypes = array_map(static fn (Arg $arg): Type => $scope->getType($arg->value), $args);

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

            $paths[] = array_map(static fn (Type $segment): string => $segment->getConstantStrings()[0]->getValue(), $segments);
        }

        return $paths === [] ? null : $paths;
    }

    /**
     * Follows $segments into $type the way data_get() does.
     *
     * @param list<Type> $segments
     *
     * @return array{Type, bool} The value type reached, and whether the default may be returned instead.
     */
    public static function select(Type $type, array $segments): array
    {
        if ($segments === []) {
            return [$type, false];
        }

        $types     = [];
        $fallsBack = false;

        foreach (self::members($type) as $memberType) {
            $hasKey = $memberType->hasOffsetValueType($segments[0]);

            if ($hasKey->no()) {
                $fallsBack = true;

                continue;
            }

            [$selectedType, $selectionFallsBack] = self::select($memberType->getOffsetValueType($segments[0]), array_slice($segments, 1));

            $types[]   = $selectedType;
            $fallsBack = $fallsBack || $selectionFallsBack || ! $hasKey->yes();
        }

        return [TypeCombinator::union(...$types), $fallsBack];
    }

    /**
     * The array only() assembles from the values found at $paths, or null when $type is not an array shape.
     *
     * @param list<list<string>> $paths
     */
    public static function selectPaths(Type $type, array $paths): Type|null
    {
        $arrayType = TypeCombinator::removeNull($type);

        if (! $arrayType->isConstantArray()->yes()) {
            return null;
        }

        $selected = [];

        foreach ($arrayType->getConstantArrays() as $constantArray) {
            $selectedType = self::selectPathsFromArray($constantArray, $paths);

            if ($selectedType === null) {
                return null;
            }

            $selected[] = $selectedType;
        }

        return TypeCombinator::union(...$selected);
    }

    /** @param list<list<string>> $paths */
    private static function selectPathsFromArray(ConstantArrayType $type, array $paths): Type|null
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
            $keyType = new ConstantStringType((string) $segment);
            $hasKey  = $type->hasOffsetValueType($keyType);

            if ($hasKey->no()) {
                continue;
            }

            $valueType = $type->getOffsetValueType($keyType);

            if ($children === true) {
                $builder->setOffsetValueType($keyType, $valueType, ! $hasKey->yes());

                continue;
            }

            $selected = self::selectPaths($valueType, $children);

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

    /**
     * The array except() leaves after removing top-level keys; nested paths are not modelled.
     *
     * @param list<list<string>> $paths
     */
    private static function forgetKeys(Type $type, array $paths): Type|null
    {
        if (! $type->isConstantArray()->yes()) {
            return null;
        }

        foreach ($paths as $path) {
            if (count($path) !== 1) {
                return null;
            }

            $type = $type->unsetOffset(new ConstantStringType($path[0]));
        }

        return $type;
    }

    /** The value data_get() produces from a default, which it invokes when it is a Closure. */
    public static function resolveDefaultType(Type $type, Scope $scope): Type
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

    /** The accessor's result for a value of $type; for an $absent key enum() and enums() skip their cast. */
    private function cast(string $method, Type $type, Type|null $enumType, Type $defaultType, bool $absent = false): Type|null
    {
        if ($type instanceof NeverType) {
            return $type;
        }

        return match ($method) {
            'integer' => self::castToInteger($type),
            'float' => self::castToFloat($type),
            'boolean' => self::castToBoolean($type),
            'enum' => $absent ? $defaultType : ($enumType === null ? null : self::castToEnum($type, $enumType, $defaultType)),
            'enums' => $absent ? new ConstantArrayType([], []) : ($enumType === null ? null : self::castToEnums($type, $enumType)),
            'array' => self::castToArray($type),
            'collect' => $this->castToCollection($type),
            default => $type,
        };
    }

    /** The result of an (int) cast, or null when the cast is invalid for a possible value. */
    public static function castToInteger(Type $type): Type|null
    {
        $types      = [];
        $hasInteger = false;
        $hasBounded = false;

        foreach (self::members($type) as $memberType) {
            if ($memberType->isInteger()->yes()) {
                $hasInteger = true;
                $types[]    = $memberType;

                continue;
            }

            if ($memberType->isConstantScalarValue()->yes()) {
                $types[] = $memberType->toInteger();

                continue;
            }

            // A numeric rule applies its bounds to every representation, so a float or numeric string truncates into the integer member.
            if ($memberType->isFloat()->yes() || $memberType->isNumericString()->yes()) {
                $hasBounded = true;

                continue;
            }

            $castType = $memberType->toInteger();

            if ($castType instanceof ErrorType) {
                return null;
            }

            $types[] = $castType;
        }

        return $hasBounded && ! $hasInteger ? $type->toInteger() : TypeCombinator::union(...$types);
    }

    /** The result of a (float) cast, or null when the cast is invalid for a possible value. */
    public static function castToFloat(Type $type): Type|null
    {
        $castType = $type->toFloat();

        return $castType instanceof ErrorType ? null : $castType;
    }

    /** The result of filter_var() with FILTER_VALIDATE_BOOLEAN. */
    public static function castToBoolean(Type $type): Type
    {
        $truthyType = TypeCombinator::union(
            new ConstantBooleanType(true),
            new ConstantIntegerType(1),
            new ConstantStringType('1'),
            new ConstantStringType('true'),
            new ConstantStringType('on'),
            new ConstantStringType('yes'),
        );

        if ($truthyType->isSuperTypeOf($type)->yes()) {
            return new ConstantBooleanType(true);
        }

        $falsyType = TypeCombinator::union(
            new ConstantBooleanType(false),
            new ConstantIntegerType(0),
            new ConstantStringType('0'),
            new ConstantStringType('false'),
            new ConstantStringType('off'),
            new ConstantStringType('no'),
            new ConstantStringType(''),
            new NullType(),
        );

        if ($falsyType->isSuperTypeOf($type)->yes()) {
            return new ConstantBooleanType(false);
        }

        return TypeCombinator::union(new ConstantBooleanType(true), new ConstantBooleanType(false));
    }

    /** The case enum() resolves through tryFrom(), or the default where the value is unfilled or matches no case. */
    public static function castToEnum(Type $type, Type $enumType, Type $defaultType): Type|null
    {
        $cases = self::enumCases($enumType);

        if ($cases === null) {
            return null;
        }

        // A unit enum has no tryFrom(), so the default is returned.
        if ($cases === []) {
            return $defaultType;
        }

        $types = [];

        foreach (self::members($type) as $memberType) {
            $scalarValues = $memberType->getConstantScalarValues();

            if (self::isUnfilled($memberType)) {
                $types[] = $defaultType;
            } elseif (count($scalarValues) !== 1) {
                $types[] = TypeCombinator::union($defaultType, ...array_values($cases));
            } else {
                $types[] = self::matchCase($cases, $scalarValues[0]) ?? $defaultType;
            }
        }

        return TypeCombinator::union(...$types);
    }

    /** The cases enums() keeps from a collected array of values; an unfilled value yields an empty array. */
    public static function castToEnums(Type $type, Type $enumType): Type|null
    {
        $cases = self::enumCases($enumType);

        if ($cases === null) {
            return null;
        }

        $types = [];

        foreach (self::members($type) as $memberType) {
            $arrayType = self::isUnfilled($memberType) || $cases === [] ? new ConstantArrayType([], []) : self::castToArray($memberType);
            $caseType  = $arrayType === null ? null : self::castToEnum($arrayType->getIterableValueType(), $enumType, new NullType());

            if ($arrayType === null || $caseType === null) {
                return null;
            }

            $caseType = TypeCombinator::removeNull($caseType);

            // filter() drops values without a case and keeps the remaining keys.
            $types[] = $caseType instanceof NeverType ? new ConstantArrayType([], []) : new ArrayType($arrayType->getIterableKeyType(), $caseType);
        }

        return TypeCombinator::union(...$types);
    }

    /** The result of an (array) cast, or null when the value may be an object. */
    public static function castToArray(Type $type): Type|null
    {
        $types = [];

        foreach (self::members($type) as $memberType) {
            if ($memberType->isNull()->yes()) {
                $types[] = new ConstantArrayType([], []);
            } elseif ($memberType->isArray()->yes()) {
                $types[] = $memberType;
            } elseif ($memberType->isScalar()->yes()) {
                $types[] = new ConstantArrayType([new ConstantIntegerType(0)], [$memberType]);
            } else {
                return null;
            }
        }

        return TypeCombinator::union(...$types);
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

    /**
     * The backed cases of $enumType keyed by backing value, an empty list for a unit enum, or null for a non-enum.
     *
     * Cases are created without a class reflection, as the scope creates them, so that both merge in unions.
     *
     * @return array<int|string, EnumCaseObjectType>|null
     */
    private static function enumCases(Type $enumType): array|null
    {
        if (! $enumType->isEnum()->yes() || $enumType->getEnumCases() === []) {
            return null;
        }

        $cases = [];

        foreach ($enumType->getEnumCases() as $case) {
            $backingType = $case->getBackingValueType();

            if ($backingType === null) {
                return [];
            }

            $backingValue = $backingType->getConstantScalarValues()[0] ?? null;

            if (! is_int($backingValue) && ! is_string($backingValue)) {
                return null;
            }

            $cases[$backingValue] = new EnumCaseObjectType($case->getClassName(), $case->getEnumCaseName());
        }

        return $cases;
    }

    /**
     * The case tryFrom() resolves $value to, after PHP's coercion of the scalar to the backing type.
     *
     * @param non-empty-array<int|string, EnumCaseObjectType> $cases
     */
    private static function matchCase(array $cases, bool|float|int|string|null $value): EnumCaseObjectType|null
    {
        $isIntegerBacked = is_int(array_key_first($cases));

        if ($isIntegerBacked && is_string($value) && ! is_numeric($value)) {
            return null;
        }

        return $cases[$isIntegerBacked ? (int) $value : (string) $value] ?? null;
    }

    /** @return list<Type> */
    private static function members(Type $type): array
    {
        return $type instanceof UnionType ? $type->getTypes() : [$type];
    }

    /** Whether isNotFilled() certainly holds: null or a string that trims to nothing. */
    private static function isUnfilled(Type $type): bool
    {
        $constantStrings = $type->getConstantStrings();

        return $type->isNull()->yes() || (count($constantStrings) === 1 && trim($constantStrings[0]->getValue()) === '');
    }

    /** Whether a value of $type can be, or contain, an uploaded file; mixed is not counted because it stays sound either way. */
    private static function mayContainUploadedFile(Type $type): bool
    {
        foreach (self::members($type) as $memberType) {
            $nestedTypes = match (true) {
                $memberType->isConstantArray()->yes() => $memberType->getConstantArrays()[0]->getValueTypes(),
                $memberType->isArray()->yes() => [$memberType->getIterableValueType()],
                default => [],
            };

            foreach ($nestedTypes as $nestedType) {
                if (self::mayContainUploadedFile($nestedType)) {
                    return true;
                }
            }

            if ($nestedTypes === [] && ! $memberType instanceof MixedType && ! (new ObjectType(UploadedFile::class))->isSuperTypeOf($memberType)->no()) {
                return true;
            }
        }

        return false;
    }
}

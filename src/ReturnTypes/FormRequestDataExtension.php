<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\ValidatedInput;
use Larastan\Larastan\Support\FormRequestHelper;
use Larastan\Larastan\Support\Validation\RuleTypes;
use Larastan\Larastan\Support\Validation\ValidatedData;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

use function count;
use function in_array;

/**
 * Types the data a form request or its validated input hands out, from the request's validation rules.
 *
 * Registered once for the request and once for {@see ValidatedInput}.
 */
final class FormRequestDataExtension implements DynamicMethodReturnTypeExtension
{
    /** Methods reading the validated data of a request. */
    private const VALIDATED = ['validated', 'safe'];

    /** Methods reading one value. On a request they do not see uploaded files. */
    private const VALUES = ['input', 'integer', 'float', 'boolean', 'enum', 'enums', 'array', 'collect'];

    private const KEYS = ['all', 'only', 'except', 'has', 'exists', 'missing'];

    /** @param class-string $class */
    public function __construct(private string $class, private bool $checkFormRequestTypes, private FormRequestHelper $helper)
    {
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        $name = $methodReflection->getName();

        if ($this->class !== FormRequest::class) {
            return $this->checkFormRequestTypes && in_array($name, [...self::VALUES, ...self::KEYS], true);
        }

        return $this->checkFormRequestTypes ? in_array($name, [...self::VALIDATED, ...self::VALUES, ...self::KEYS], true) : $name === 'safe';
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
    {
        $name      = $methodReflection->getName();
        $arguments = [];

        foreach ($methodCall->getArgs() as $argument) {
            if ($argument->unpack) {
                return null;
            }

            $arguments[] = $scope->getType($argument->value);
        }

        if (! $this->checkFormRequestTypes) {
            return self::untypedKeys($arguments[0] ?? new NullType());
        }

        $receiver = $scope->getType($methodCall->var);
        $types    = [];

        foreach ($this->sources($receiver, $scope, $name) ?? [null] as $data) {
            $type = $data === null ? null : $this->call($name, $data, $arguments);

            if ($type === null) {
                return null;
            }

            $types[] = $type;
        }

        return TypeCombinator::union(...$types);
    }

    /** @return list<Type>|null The data every class of the receiver reads from, or null when one of them is not known. */
    private function sources(Type $receiver, Scope $scope, string $method): array|null
    {
        if ($this->class !== FormRequest::class) {
            $data = $receiver->getTemplateType(ValidatedInput::class, 'TData');

            return $data->isArray()->yes() ? [$data] : null;
        }

        if ($this->helper->isBeforeValidation($receiver, $scope)) {
            return null;
        }

        $sources = [];

        foreach ($this->helper->requests($receiver) as $request) {
            $shapes = $this->helper->inherits($request, $method) ? $this->helper->shapes($request) : null;

            if ($shapes === null) {
                return null;
            }

            $sources[] = $shapes[in_array($method, self::VALIDATED, true) ? 0 : 1];
        }

        return $sources === [] ? null : $sources;
    }

    /** @param list<Type> $arguments */
    private function call(string $method, Type $data, array $arguments): Type|null
    {
        $key  = $arguments[0] ?? new NullType();
        $keys = ValidatedData::keys($arguments);

        if ($this->class === FormRequest::class && in_array($method, self::VALUES, true)) {
            $value = ValidatedData::lookup($data, ValidatedData::segments($key) ?? [])[0];

            if ($value !== null && in_array(UploadedFile::class, $value->getReferencedClasses(), true)) {
                return null;
            }
        }

        return match ($method) {
            'validated', 'input' => $key->isNull()->yes() ? $data : ValidatedData::get($data, $key, $arguments[1] ?? new NullType()),
            'all' => $key->isNull()->yes() ? $data : null,
            'integer' => ValidatedData::get($data, $key, $arguments[1] ?? new ConstantIntegerType(0), self::toInteger(...)),
            'float' => ValidatedData::get($data, $key, $arguments[1] ?? new ConstantFloatType(0.0), self::toFloat(...)),
            'boolean' => ValidatedData::get($data, $key, $arguments[1] ?? new ConstantBooleanType(false), self::toBoolean(...)),
            'enum', 'enums' => self::enum($method, $data, $arguments),
            'array' => self::toArray($data, $key),
            'collect' => self::collect(self::toArray($data, $key)),
            default => $keys === null ? null : match ($method) {
                'safe' => $key->isNull()->yes() ? new GenericObjectType(ValidatedInput::class, [$data]) : ValidatedData::only($data, $keys),
                'only' => ValidatedData::only($data, $keys),
                'except' => self::except($data, $keys),
                'missing' => ValidatedData::has($data, $keys)->negate()->toBooleanType(),
                default => ValidatedData::has($data, $keys)->toBooleanType(),
            },
        };
    }

    /** @param list<Type> $keys */
    private static function except(Type $data, array $keys): Type|null
    {
        foreach ($keys as $key) {
            $segments = ValidatedData::segments($key);

            // Nested keys are removed from within their parents, which is not modelled.
            if ($segments === null || count($segments) !== 1) {
                return null;
            }

            $data = $data->unsetOffset((new ConstantStringType($segments[0]))->toArrayKey());
        }

        return $data;
    }

    private static function toInteger(Type $value): Type
    {
        $integers = TypeCombinator::intersect($value, new IntegerType());

        // Size rules narrow the integers of a numeric value on behalf of its floats and strings.
        if (! $integers instanceof NeverType && RuleTypes::numeric()->isSuperTypeOf($value)->yes()) {
            return $integers instanceof UnionType ? TypeCombinator::union(...$integers->getTypes()) : $integers;
        }

        $integer = $value->toInteger();

        return $integer instanceof ErrorType ? new IntegerType() : $integer;
    }

    private static function toFloat(Type $value): Type
    {
        $float = $value->toFloat();

        return $float instanceof ErrorType ? new FloatType() : $float;
    }

    private static function toBoolean(Type $value): Type
    {
        foreach (['Accepted' => true, 'Declined' => false] as $rule => $boolean) {
            if (RuleTypes::valueType($rule)?->isSuperTypeOf($value)->yes()) {
                return new ConstantBooleanType($boolean);
            }
        }

        return new BooleanType();
    }

    private static function toArray(Type $data, Type $key): Type|null
    {
        $value = ValidatedData::get($data, $key, new NullType());

        return $value === null || $value instanceof MixedType ? null : $value->toArray();
    }

    private static function collect(Type|null $items): Type|null
    {
        return $items === null ? null : new GenericObjectType(Collection::class, [$items->getIterableKeyType(), $items->getIterableValueType()]);
    }

    /**
     * The cases `tryFrom()` may find for the value, or for each item of it.
     *
     * @param list<Type> $arguments
     */
    private static function enum(string $method, Type $data, array $arguments): Type|null
    {
        $key      = $arguments[0] ?? new NullType();
        $cases    = ($arguments[1] ?? new NullType())->getClassStringObjectType()->getEnumCases();
        $segments = ValidatedData::segments($key);
        $items    = $method === 'enums' ? self::toArray($data, $key) : null;

        if ($cases === [] || $segments === null || ($method === 'enums' && $items === null)) {
            return null;
        }

        [$value, $exists] = ValidatedData::lookup($data, $segments);
        $value            = $items?->getIterableValueType() ?? $value ?? new MixedType();
        $backingValues    = [];

        // Enums without backing values are never looked up.
        foreach ($cases as $i => $case) {
            $backingValue = $case->getBackingValueType();

            if ($backingValue === null || $value->isSuperTypeOf($backingValue)->no()) {
                unset($cases[$i]);
            } else {
                $backingValues[] = $backingValue;
            }
        }

        if ($items !== null) {
            return $cases === [] ? new ConstantArrayType([], []) : new ArrayType($items->getIterableKeyType(), TypeCombinator::union(...$cases));
        }

        $found = TypeCombinator::union(...($exists->no() ? [] : $cases));

        if ($exists->yes() && TypeCombinator::union(...$backingValues)->isSuperTypeOf($value)->yes()) {
            return $found;
        }

        // A case that is both found and the default would be listed twice.
        return TypeCombinator::union($found, TypeCombinator::remove(ValidatedData::value($arguments[2] ?? new NullType()), $found));
    }

    /** Without rule inference, the selected keys are all that is known about the result of `safe()`. */
    private static function untypedKeys(Type $keys): Type|null
    {
        if (! $keys->isConstantArray()->yes()) {
            return null;
        }

        $builder = ConstantArrayTypeBuilder::createEmpty();

        foreach ($keys->getIterableValueType()->getConstantStrings() as $key) {
            $builder->setOffsetValueType($key, new MixedType());
        }

        return $builder->getArray();
    }
}

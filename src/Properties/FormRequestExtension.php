<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Reflection\ReflectionHelper;
use Larastan\Larastan\Support\FormRequestHelper;
use Larastan\Larastan\Support\Validation\ValidatedData;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertiesClassReflectionExtension;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/** Gives the fields a form request has rules for as properties of the request. */
final class FormRequestExtension implements PropertiesClassReflectionExtension
{
    public function __construct(private FormRequestHelper $helper)
    {
    }

    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        return $classReflection->is(FormRequest::class)
            && ! ReflectionHelper::hasPropertyTag($classReflection, $propertyName)
            && $this->type($classReflection, $propertyName) !== null;
    }

    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $type = $this->type($classReflection, $propertyName) ?? new MixedType();

        return new ModelProperty($classReflection, $type, $type);
    }

    /** The input value of a field with rules, or null when the request has no rules for the field. */
    private function type(ClassReflection $classReflection, string $propertyName): Type|null
    {
        $input = $this->helper->shapes($classReflection)[1] ?? null;

        if ($input === null) {
            return null;
        }

        foreach ($input->getConstantArrays() as $shape) {
            foreach ($shape->getKeyTypes() as $key) {
                if ((string) $key->getValue() !== $propertyName) {
                    continue;
                }

                [$value, $exists] = ValidatedData::lookup($input, [$propertyName]);
                $value          ??= new MixedType(true);

                // A field that was not submitted reads as null.
                return $exists->yes() ? $value : TypeCombinator::addNull($value);
            }
        }

        return null;
    }
}

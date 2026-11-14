<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

/**
 * What the rules of a field say about its value.
 *
 * @internal
 */
final class Field
{
    /**
     * @param Type              $type     Values passing the rules, before `required` narrows them.
     * @param bool              $filled   The value is never null or an empty string.
     * @param bool              $present  The key always exists.
     * @param TrinaryLogic      $excluded Whether the field is removed from the validated data.
     * @param TrinaryLogic      $bare     Whether a parameterless `array` or `list` rule limits validated data to validated children.
     * @param list<string>|null $keys     The only keys an array value may have.
     * @param bool              $unknown  The rules could not be read.
     */
    public function __construct(
        public Type $type,
        public bool $filled,
        public bool $present,
        public TrinaryLogic $excluded,
        public TrinaryLogic $bare,
        public array|null $keys = null,
        public bool $unknown = false,
    ) {
    }

    public static function unknown(): self
    {
        return new self(new MixedType(true), false, false, TrinaryLogic::createNo(), TrinaryLogic::createNo(), null, true);
    }

    /** Combines two readings of the same field; null when they disagree on the array keys. */
    public function merge(self $other): self|null
    {
        if ($this->unknown || $other->unknown || ! $this->excluded->equals($other->excluded)) {
            return self::unknown();
        }

        if ($this->keys !== $other->keys) {
            return null;
        }

        return new self(
            TypeCombinator::union($this->type, $other->type),
            $this->filled && $other->filled,
            $this->present && $other->present,
            $this->excluded,
            TrinaryLogic::extremeIdentity($this->bare, $other->bare),
            $this->keys,
        );
    }

    public function valueType(): Type
    {
        if (! $this->filled || $this->type instanceof MixedType) {
            return $this->type;
        }

        return TypeCombinator::remove(TypeCombinator::removeNull($this->type), new ConstantStringType(''));
    }
}

<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class ModelPropertyType extends StringType
{
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return 'model-property';
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type instanceof ConstantStringType) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof StringType) {
            return TrinaryLogic::createMaybe();
        }

        return TrinaryLogic::createNo();
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        if ($type instanceof ConstantStringType) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }

        if ($type instanceof parent) {
            return TrinaryLogic::createMaybe();
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return TrinaryLogic::createNo();
    }

    /**
     * @param mixed[] $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }
}

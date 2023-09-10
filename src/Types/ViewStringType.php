<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

use function count;

/**
 * The custom 'view-string' type class. It's a subset of the string type. Every string that passes the
 * view()->exists($string) test is a valid view-string type.
 */
class ViewStringType extends StringType
{
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return 'view-string';
    }

    public function acceptsWithReason(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedWithReasonBy($this, $strictTypes);
        }

        $constantStrings = $type->getConstantStrings();

        if (count($constantStrings) === 1) {
            /** @var \Illuminate\View\Factory $view */
            $view = view();

            return AcceptsResult::createFromBoolean($view->exists($constantStrings[0]->getValue()));
        }

        if ($type instanceof self) {
            return AcceptsResult::createYes();
        }

        if ($type->isString()->yes()) {
            return AcceptsResult::createMaybe();
        }

        return AcceptsResult::createNo();
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        $constantStrings = $type->getConstantStrings();

        if (count($constantStrings) === 1) {
            /** @var \Illuminate\View\Factory $view */
            $view = view();

            return TrinaryLogic::createFromBoolean($view->exists($constantStrings[0]->getValue()));
        }

        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }

        if ($type->isString()->yes()) {
            return TrinaryLogic::createMaybe();
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return TrinaryLogic::createNo();
    }

    /**
     * @param  mixed[]  $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }
}

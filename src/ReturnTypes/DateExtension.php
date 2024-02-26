<?php

declare(strict_types=1);

namespace Larastan\Larastan\ReturnTypes;

use Illuminate\Support\Facades\Date;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

use function get_class;
use function in_array;
use function now;

class DateExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Date::class;
    }

    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array($methodReflection->getName(), [
            'create',
            'createFromDate',
            'createFromTime',
            'createFromTimeString',
            'createFromTimestamp',
            'createFromTimestampMs',
            'createFromTimestampUTC',
            'createMidnightDate',
            'fromSerialized',
            'getTestNow',
            'instance',
            'maxValue',
            'minValue',
            'now',
            'parse',
            'today',
            'tomorrow',
            'yesterday',
            'createFromFormat',
            'createSafe',
            'make',
        ], true);
    }

    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope,
    ): Type {
        $dateType = new ObjectType(get_class(now()));

        if (in_array($methodReflection->getName(), ['createFromFormat', 'createSafe'], true)) {
            return TypeCombinator::union($dateType, new ConstantBooleanType(false));
        }

        if (in_array($methodReflection->getName(), ['getTestNow', 'make'], true)) {
            return TypeCombinator::addNull($dateType);
        }

        return $dateType;
    }
}

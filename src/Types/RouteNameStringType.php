<?php

namespace NunoMaduro\Larastan\Types;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function app;
use function view;

class RouteNameStringType extends StringType
{
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return 'route-name-string';
    }

    private function exists(string $name): bool
    {
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }

        if ($type instanceof ConstantStringType) {
            /** @var \Illuminate\Routing\Router $router */
            $router = app('router');

            return TrinaryLogic::createFromBoolean($router->has($type->getValue()));
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
            /** @var \Illuminate\Routing\Router $router */
            $router = app('router');

            return TrinaryLogic::createFromBoolean($router->has($type->getValue()));
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

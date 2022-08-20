<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types\Factory;

use PHPStan\Reflection\ClassReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class ModelFactoryType extends ObjectType
{
    private TrinaryLogic $isSingleModel;

    public function __construct(
        string $className,
        ?Type $subtractedType = null,
        ?ClassReflection $classReflection = null,
        ?TrinaryLogic $isSingleModel = null,
    ) {
        parent::__construct($className, $subtractedType, $classReflection);

        $this->isSingleModel = $isSingleModel ?? TrinaryLogic::createMaybe();
    }

    public function isSingleModel(): TrinaryLogic
    {
        return $this->isSingleModel;
    }
}

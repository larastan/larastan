<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types\Factory;

use PHPStan\Reflection\ClassReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

class ModelFactoryType extends ObjectType
{
    private TrinaryLogic $isSingleModel;

    public function __construct(
        string $className,
        Type|null $subtractedType = null,
        ClassReflection|null $classReflection = null,
        TrinaryLogic|null $isSingleModel = null,
    ) {
        parent::__construct($className, $subtractedType, $classReflection);

        $this->isSingleModel = $isSingleModel ?? TrinaryLogic::createMaybe();
    }

    public function isSingleModel(): TrinaryLogic
    {
        return $this->isSingleModel;
    }
}

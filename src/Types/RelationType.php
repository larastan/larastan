<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use NunoMaduro\Larastan\Reflection\RelationClassReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\VerbosityLevel;

final class RelationType extends ObjectType
{
    /**
     * @var string
     */
    private $relationClass;

    /**
     * @var string
     */
    private $relatedModel;

    public function __construct(string $relationClass, string $relatedModel)
    {
        parent::__construct($relationClass);

        $this->relationClass = $relationClass;
        $this->relatedModel = $relatedModel;
    }

    public function getRelationClass(): string
    {
        return $this->relationClass;
    }

    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }

    public function getClassReflection(): ?ClassReflection
    {
        $classReflection = parent::getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        return new RelationClassReflection($this->relatedModel, $classReflection);
    }

    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->relatedModel);
    }
}

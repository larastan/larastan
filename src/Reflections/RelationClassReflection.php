<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Reflections;

use PHPStan\Reflection\ClassReflection;

class RelationClassReflection extends ClassReflection
{
    /**
     * @var string
     */
    private $relatedModel;

    /**
     * @param string          $relatedModel
     * @param ClassReflection $baseClassReflection
     */
    public function __construct(string $relatedModel, ClassReflection $baseClassReflection)
    {
        $this->relatedModel = $relatedModel;
        $privatePropertyAccessor = function ($prop) {
            return $this->$prop;
        };

        parent::__construct(
            $privatePropertyAccessor->call($baseClassReflection, 'reflectionProvider'), $privatePropertyAccessor->call($baseClassReflection, 'fileTypeMapper'), $privatePropertyAccessor->call($baseClassReflection, 'propertiesClassReflectionExtensions'),
            $privatePropertyAccessor->call($baseClassReflection, 'methodsClassReflectionExtensions'), $privatePropertyAccessor->call($baseClassReflection, 'displayName'), $privatePropertyAccessor->call($baseClassReflection, 'reflection'), $privatePropertyAccessor->call($baseClassReflection, 'anonymousFilename'), $privatePropertyAccessor->call($baseClassReflection, 'resolvedTemplateTypeMap'),
            $privatePropertyAccessor->call($baseClassReflection, 'stubPhpDocBlock')
        );
    }

    /**
     * @return string
     */
    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }
}

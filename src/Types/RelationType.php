<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan\Types;

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

    /**
     * @return string
     */
    public function getRelationClass(): string
    {
        return $this->relationClass;
    }

    /**
     * @return string
     */
    public function getRelatedModel(): string
    {
        return $this->relatedModel;
    }

    public function describe(VerbosityLevel $level): string
    {
        return sprintf('%s<%s>', parent::describe($level), $this->relatedModel);
    }
}

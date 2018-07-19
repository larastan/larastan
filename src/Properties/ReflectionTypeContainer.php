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

namespace NunoMaduro\Larastan\Properties;

use ReflectionType;

/**
 * @internal
 */
final class ReflectionTypeContainer extends ReflectionType
{
    /**
     * @var string
     */
    private $type;

    /**
     * ReflectionTypeContainer constructor.
     *
     * @param string $type
     */
    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function allowsNull(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isBuiltin(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->type;
    }
}

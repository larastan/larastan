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

namespace NunoMaduro\Larastan\Database\Eloquent;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\Larastan\AbstractExtension;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @internal
 */
class ModelMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected $staticAccess = true;

    /**
     * {@inheritdoc}
     */
    protected function subject(ClassReflection $classReflection, string $methodName): array
    {
        return [Model::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection, string $methodName): array
    {
        return [
            EloquentBuilder::class,
            QueryBuilder::class,
            BelongsToMany::class,
        ];
    }
}

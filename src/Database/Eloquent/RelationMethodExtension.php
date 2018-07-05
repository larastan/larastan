<?php

declare(strict_types=1);

/**
 * This file is part of Laravel Code Analyse.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\LaravelCodeAnalyse\Database\Eloquent;

use PHPStan\Reflection\ClassReflection;
use NunoMaduro\LaravelCodeAnalyse\AbstractExtension;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @internal
 */
final class RelationMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subject(): string
    {
        return Relation::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection): array
    {
        return [
            EloquentBuilder::class,
            QueryBuilder::class,
            BelongsToMany::class,
        ];
    }
}

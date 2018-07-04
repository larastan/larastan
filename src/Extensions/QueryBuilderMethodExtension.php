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

namespace NunoMaduro\LaravelCodeAnalyse\Extensions;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;

final class QueryBuilderMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected $staticAccess = true;

    /**
     * {@inheritdoc}
     */
    protected function subject(): string
    {
        return Model::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(): string
    {
        return Builder::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function staticAccess(): bool
    {
        return true;
    }
}



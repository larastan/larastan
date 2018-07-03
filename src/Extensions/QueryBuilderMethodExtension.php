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

final class QueryBuilderMethodExtension extends AbstractBuilderMethodExtension
{
    /**
     * {@inheritdoc}
     */
    public function getBuilderClass(): string
    {
        return Builder::class;
    }
}

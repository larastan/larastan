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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class BelongsToManyMethodExtension extends AbstractExtension
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
        return BelongsToMany::class;
    }
}

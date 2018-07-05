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

namespace NunoMaduro\Larastan\Http\Resources\Json;

use NunoMaduro\Larastan\Concerns\HasScope;

/**
 * @internal
 */
final class ResourceScopeMethodExtension extends ResourceMethodExtension
{
    use HasScope;
}

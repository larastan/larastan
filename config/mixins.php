<?php

declare(strict_types=1);

/*
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;

return [

    /*
    |--------------------------------------------------------------------------
    | Mixins
    |--------------------------------------------------------------------------
    */

    Model::class => [
        Builder::class,
    ],
    JsonResource::class => collect(get_declared_classes())
        ->filter(
            function ($item) {
                return (new ReflectionClass($item))->isSubclassOf(Model::class);
            }
        )
        ->toArray(),
];

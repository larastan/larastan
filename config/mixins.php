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

use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

return [

    /*
    |--------------------------------------------------------------------------
    | Mixins
    |--------------------------------------------------------------------------
    */

    Model::class => [
        Builder::class,
    ],

    LengthAwarePaginatorContract::class => [
        LengthAwarePaginator::class,
        Collection::class,
    ],

    PaginatorContract::class => [
        Collection::class,
        Paginator::class,
    ],

    Collection::class => [
        Model::class,
    ],

    JsonResource::class => collect(get_declared_classes())
        ->filter(
            function ($item) {
                return (new ReflectionClass($item))->isSubclassOf(Model::class);
            }
        )
        ->toArray(),
];

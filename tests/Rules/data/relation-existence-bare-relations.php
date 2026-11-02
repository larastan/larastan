<?php

declare(strict_types=1);

namespace RelationExistenceBareRelations;

use App\BareRelations\Owner;
use App\User;
use Illuminate\Database\Eloquent\Builder;

Owner::query()->whereHas('items.category', static function (Builder $query): void {
    $query->whereHas('labels', static function (Builder $query): void {
        $query->whereKey(1);
    });
});

Owner::query()->whereHas('items', static function (Builder $query): void {
    $query->whereHas('category');
});

User::query()->whereHas('posts.comments', static function (Builder $query): void {
    $query->whereHas('missing');
});

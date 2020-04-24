<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UnnecessaryCollectionCallsQuery
{
    public function queryMax(): int
    {
        return DB::table('users')->get()->max('id');
    }

    public function queryAverageNoParam(): float
    {
        return DB::table('users')->pluck('id')->average();
    }

    public function queryNotEmpty(): bool
    {
        return DB::table('users')->pluck('id')->isNotEmpty();
    }

    public function queryPluck(): Collection
    {
        return DB::table('users')->get()->pluck('arbitraryName');
    }
}

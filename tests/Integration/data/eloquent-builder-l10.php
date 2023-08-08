<?php

namespace EloquentBuilderLaravel10;

use App\User;
use Illuminate\Support\Facades\DB;

User::query()->where(DB::raw('1'), 1)->get();

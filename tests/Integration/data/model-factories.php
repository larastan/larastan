<?php

namespace ModelFactories;

use App\User;
use Illuminate\Database\Eloquent\Factories\Sequence;

function test(): void {
    User::factory()->create(new Sequence());
    User::factory()->createQuietly(new Sequence());
    User::factory()->make(new Sequence());
}

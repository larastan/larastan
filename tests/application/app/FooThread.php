<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;

class FooThread extends Model
{
    protected $connection = 'foo';

    // This is testing Postgres schemas (sub-databases)
    protected $table = 'private.threads';
}

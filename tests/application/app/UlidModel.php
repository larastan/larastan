<?php

namespace App;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class UlidModel extends Model
{
    use HasUlids;
}

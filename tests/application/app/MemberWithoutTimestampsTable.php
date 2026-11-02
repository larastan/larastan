<?php

namespace App;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'users', timestamps: false)]
class MemberWithoutTimestampsTable extends Model
{
}

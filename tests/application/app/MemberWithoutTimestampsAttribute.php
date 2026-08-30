<?php

namespace App;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'users')]
#[WithoutTimestamps]
class MemberWithoutTimestampsAttribute extends Model
{
}

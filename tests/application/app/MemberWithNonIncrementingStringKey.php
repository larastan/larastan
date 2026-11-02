<?php

namespace App;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(keyType: 'string', incrementing: false)]
class MemberWithNonIncrementingStringKey extends Model
{
}

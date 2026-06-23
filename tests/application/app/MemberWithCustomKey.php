<?php

namespace App;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'users', key: 'uuid', keyType: 'string')]
class MemberWithCustomKey extends Model
{
}

<?php

namespace Bug2002;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
}

function test(): void
{
    Invoice::query()->join('users', 'users.id', '=', 'invoices.user_id')->where('invoices.status', 1);
    User::query()->join('roles as r', 'users.role_id', '=', 'r.id')->where('r.name', 'admin');
}

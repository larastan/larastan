<?php

use App\User;
use Illuminate\Support\Facades\DB;

User::query()->findSole(1, ['foo']);
User::query()->findSole(1, 'foo');
User::query()->findSole(1, ['id', 'email']);
User::query()->findSole(1, '*');
User::query()->findSole(1, DB::raw('name'));
User::findSole(1, ['foo']);
(new User())->accounts()->findSole(1, ['foo']);

User::query()->findOr(1, ['foo'], fn () => null);
User::query()->findOr(1, 'foo', fn () => null);
User::query()->findOr(1, ['id', 'email'], fn () => null);
User::query()->findOr(1, [DB::raw('name')], fn () => null);
User::query()->findOr(1, fn () => null);
User::query()->findOr(1, callback: fn () => null);
User::findOr(1, ['foo'], fn () => null);
(new User())->accounts()->findOr(1, ['foo'], fn () => null);

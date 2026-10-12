<?php

use App\User;

User::query()->incrementEach(['foo' => 1]);
User::query()->incrementEach(['id' => 1]);
User::query()->incrementEach(['id' => 1], ['foo' => 'bar']);
User::query()->incrementEach(['id' => 1], ['name' => 'bar']);

User::query()->decrementEach(['foo' => 1]);
User::query()->decrementEach(['id' => 1]);
User::query()->decrementEach(['id' => 1], ['foo' => 'bar']);
User::query()->decrementEach(['id' => 1], ['name' => 'bar']);

(new User())->accounts()->incrementEach(['foo' => 1]);
(new User())->accounts()->decrementEach(['foo' => 1]);

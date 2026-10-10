<?php

use App\User;

User::query()->incrementOrCreate(['foo' => 'bar']);
User::query()->incrementOrCreate(['email' => 'taylor@example.com']);
User::query()->incrementOrCreate(['email' => 'taylor@example.com'], 'foo');
User::query()->incrementOrCreate(['email' => 'taylor@example.com'], 'integer');
User::query()->incrementOrCreate(['email' => 'taylor@example.com'], 'integer', 1, 1, ['foo' => 'bar']);
User::query()->incrementOrCreate(['email' => 'taylor@example.com'], 'integer', 1, 1, ['name' => 'bar']);

User::incrementOrCreate(['foo' => 'bar']);
(new User())->accounts()->incrementOrCreate(['foo' => 'bar']);

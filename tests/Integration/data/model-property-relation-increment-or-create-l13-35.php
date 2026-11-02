<?php

use App\User;

(new User())->accounts()->incrementOrCreate(['foo' => 'bar']);
(new User())->accounts()->incrementOrCreate(['name' => 'taylor']);
(new User())->accounts()->incrementOrCreate(['name' => 'taylor'], 'foo');
(new User())->accounts()->incrementOrCreate(['name' => 'taylor'], 'active');
(new User())->accounts()->incrementOrCreate(['name' => 'taylor'], 'active', 1, 1, ['foo' => 'bar']);
(new User())->accounts()->incrementOrCreate(['name' => 'taylor'], 'active', 1, 1, ['name' => 'otwell']);

(new User())->oneAddress()->incrementOrCreate(['foo' => 'bar']);
(new User())->address()->incrementOrCreate(['foo' => 'bar']);

(new User())->roles()->incrementOrCreate(['foo' => 'bar']);
(new User())->roles()->incrementOrCreate(['name' => 'admin']);
(new User())->roles()->incrementOrCreate(['name' => 'admin'], 'foo');
(new User())->roles()->incrementOrCreate(['name' => 'admin'], 'name', 1, 1, ['foo' => 'bar']);

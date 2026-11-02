<?php

use App\User;

(new User())->transactions()->incrementOrCreate(['foo' => 'bar']);
(new User())->transactions()->incrementOrCreate([]);
(new User())->car()->incrementOrCreate([], 'foo');
(new User())->car()->incrementOrCreate([], 'foo', 1, 1, ['foo' => 'bar']);

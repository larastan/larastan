<?php

use App\User;

(new User())->accounts()->incrementOrCreate(['foo' => 'bar']);

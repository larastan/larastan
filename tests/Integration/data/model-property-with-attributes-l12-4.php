<?php

use App\User;

User::query()->withAttributes(['email' => 'taylor@example.com', 'foo' => 'bar'], asConditions: false);
User::query()->withAttributes('foo', 'bar', false);

User::query()->withAttributes(['email' => 'taylor@example.com'], asConditions: false);
User::query()->withAttributes('email', 'taylor@example.com', false);

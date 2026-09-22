<?php

use App\User;

User::query()->forceCreateQuietly(['foo' => 'bar']);
User::query()->forceCreateQuietly(['id' => 1]);
User::query()->forceCreateQuietly();

User::forceCreateQuietly(['foo' => 'bar']);
User::forceCreateQuietly(['id' => 1]);
User::forceCreateQuietly();

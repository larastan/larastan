<?php

namespace App\Console;

use App\Console\Commands\BarCommand;
use App\Console\Commands\BarCommandWithAttribute;
use App\Console\Commands\BazCommand;
use App\Console\Commands\FooCommand;
use Illuminate\Console\Application as Artisan;

class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function commands(): void
    {
        Artisan::starting(function (Artisan $artisan) {
            $artisan->resolve(FooCommand::class);
            $artisan->resolve(BarCommand::class);
            $artisan->resolve(BazCommand::class);
            $artisan->resolve(BarCommandWithAttribute::class);
        });
    }

    protected function bootstrappers(): array
    {
        return [];
    }
}

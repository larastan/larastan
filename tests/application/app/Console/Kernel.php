<?php

namespace App\Console;

use App\Console\Commands\BarCommand;
use App\Console\Commands\BazCommand;
use App\Console\Commands\FooCommand;
use Illuminate\Console\Application as Artisan;

class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function commands(): void
    {
        Artisan::starting(function ($artisan) {
            $artisan->resolve(FooCommand::class);
            $artisan->resolve(BarCommand::class);
            $artisan->resolve(BazCommand::class);
        });
    }

    protected function bootstrappers(): array
    {
        return [];
    }
}

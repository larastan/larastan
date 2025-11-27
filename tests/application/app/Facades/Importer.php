<?php

namespace App\Facades;

use App\Importer as Instance;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;

class Importer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Instance::class;
    }

    public function facadeMethod(): string
    {
        return Str::random(5);
    }
}

<?php

namespace App;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string foo()
 * @method static int bar()
 */
class DummyFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'dummy';
    }
}

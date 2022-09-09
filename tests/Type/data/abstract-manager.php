<?php

namespace AbstractManager;

use Illuminate\Container\Container;
use Illuminate\Support\Manager;
use function PHPStan\Testing\assertType;

abstract class AbstractManager extends Manager
{
}

class RealManager extends AbstractManager
{
    public function getDefaultDriver()
    {
        return '';
    }
}

assertType('AbstractManager\RealManager', new RealManager(new Container()));

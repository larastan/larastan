<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Auth\AuthManager;
use Illuminate\Container\Container;

class ContainerArrayAccessTest
{
    /** @var Container */
    protected $app;

    public function testAuth(): AuthManager
    {
        return $this->app['auth'];
    }
}

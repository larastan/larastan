<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Foundation\Application;

class ContainerArrayAccessTest
{
    /** @var Application */
    protected $app;

    public function testAuth(): AuthManager
    {
        return $this->app['auth'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Foundation\Application;

class ApplicationExtension
{
    /**
     * @return bool|string
     */
    public function testApplicationEnvironment()
    {
        $app = new Application;
        return $app->environment('production');
    }

}

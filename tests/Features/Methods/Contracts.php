<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Session\Session;

class Contracts
{
    /** @var Application */
    private $app;

    /** @var Session */
    private $session;

    public function testApplicationIsLocal(): bool
    {
        return $this->app->isLocal();
    }

    public function testSessionAgeFlashData(): void
    {
        $this->session->ageFlashData();
    }
}

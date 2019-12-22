<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Support\Facades\Event;

class Facades
{
    public function testEventAssertDispatched(): void
    {
        Event::assertDispatched('FooEvent');
    }

    public function testEventAssertDispatchedTimes(): void
    {
        Event::assertDispatchedTimes('FooEvent', 5);
    }

    public function testEventAssertNotDispatched(): void
    {
        Event::assertNotDispatched('FooEvent');
    }
}

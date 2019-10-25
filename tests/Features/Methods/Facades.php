<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Illuminate\Support\Facades\Event;

class Facades
{
    public function testEventAssertDispatched()
    {
        Event::assertDispatched('FooEvent');
    }
    
    public function testEventAssertDispatchedTimes()
    {
        Event::assertDispatchedTimes('FooEvent', 5);
    }
    
    public function testEventAssertNotDispatched()
    {
        Event::assertNotDispatched('FooEvent');
    }
}

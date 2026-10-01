<?php

namespace EventDispatcherTypes;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use function PHPStan\Testing\assertType;

function dispatch(Dispatcher $dispatcher, DispatcherContract $contract, bool $halt): void
{
    assertType('mixed', $dispatcher->dispatch('event', [], true));
    assertType('mixed', $contract->dispatch('event', [], true));
    assertType('mixed', Event::dispatch('event', [], true));
    assertType('mixed', $dispatcher->dispatch('event', halt: $halt));
    assertType('list<mixed>|null', $dispatcher->dispatch('event'));
    assertType('array<mixed>|null', $contract->dispatch('event'));
    assertType('array<mixed>|null', Event::dispatch('event'));
    assertType('mixed', Event::dispatch(halt: true, event: new \stdClass()));
}

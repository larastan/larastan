<?php

namespace Tests\Rules\Data;

LaravelEvent::dispatch();

LaravelEvent::dispatch('foo');
LaravelEvent::dispatch(1, 'foo');
LaravelEvent::dispatch(true, false);

LaravelEvent::dispatchIf(true, 1, 'foo');
LaravelEvent::dispatchUnless(true, 1, 1);

LaravelEventWithoutConstructor::dispatch();
LaravelEventWithoutConstructor::dispatch('foo');

LaravelEventWithoutConstructor::dispatchIf(true);
LaravelEventWithoutConstructor::dispatchUnless(true, 'foo');

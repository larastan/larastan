<?php

namespace Tests\Rules\Data;

LaravelJob::dispatch();
LaravelJob::dispatchSync();
LaravelJob::dispatchNow();
LaravelJob::dispatchAfterResponse();

LaravelJob::dispatch('foo');
LaravelJob::dispatch(1, 'foo');
LaravelJob::dispatch(true, false);

LaravelJob::dispatchSync('foo');
LaravelJob::dispatchSync(1, 'foo');
LaravelJob::dispatchSync(true, false);

LaravelJob::dispatchNow('foo');
LaravelJob::dispatchNow(1, 'foo');
LaravelJob::dispatchNow(true, false);

LaravelJob::dispatchAfterResponse('foo');
LaravelJob::dispatchAfterResponse(1, 'foo');
LaravelJob::dispatchAfterResponse(true, false);

LaravelJob::dispatchIf(true, 1, 'foo');
LaravelJob::dispatchUnless(true, 1, 1);

LaravelJobWithoutConstructor::dispatch();
LaravelJobWithoutConstructor::dispatch('foo');
LaravelJobWithoutConstructor::dispatchSync();
LaravelJobWithoutConstructor::dispatchSync('foo');
LaravelJobWithoutConstructor::dispatchNow();
LaravelJobWithoutConstructor::dispatchNow('foo');
LaravelJobWithoutConstructor::dispatchAfterResponse();
LaravelJobWithoutConstructor::dispatchAfterResponse('foo');

LaravelJobWithoutConstructor::dispatchIf(true);
LaravelJobWithoutConstructor::dispatchUnless(true, 'foo');

LaravelJob::dispatchIf(foo: 1, bar: 'foo', boolean: true);
LaravelJob::dispatch(foo: 'foo', bar: 1);
LaravelJob::dispatch(bar: 'foo', foo: 1);
LaravelJob::dispatch(...['foo', 1]);
LaravelJob::dispatch(...[1, 'foo']);
LaravelJob::dispatchIf(true, ...['foo', 1]);
LaravelJob::dispatchUnless(true, ...[1, 'foo']);
LaravelJob::dispatch(...);
UnknownJob::dispatch();

LaravelJobWithPrivateConstructor::dispatch('foo');
LaravelJobWithPrivateConstructor::dispatch(1);

<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;

Bus::batch([
    new UniqueJobWithUniqueForProperty(),
    new RegularJob(1),
]);

Bus::bulk([
    new UniqueJobWithUniqueForMethod(),
]);

Queue::bulk([
    new UniqueJobWithUniqueForProperty(),
]);

// Chains within a batch are nested arrays and are inspected too.
Bus::batch([
    [
        new RegularJob(1),
        new UniqueJobWithUniqueForProperty(),
    ],
]);

// Not flagged: none of these are ShouldBeUnique.
Bus::batch([
    new RegularJob(1),
    new RegularJob(2),
]);

// Not flagged: dispatching a unique job on its own is the supported path.
UniqueJobWithUniqueForProperty::dispatch();

/** @var list<UniqueJobWithUniqueForProperty> $jobs */
$jobs = [];

Bus::batch($jobs);

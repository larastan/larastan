<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Support\Facades\Bus;

Bus::batch([
    new BatchableJobWithCancellationCheck(),
    new RegularJob(1),
]);

// Chains within a batch are nested arrays and need the trait too.
Bus::batch([
    [
        new BatchableJobWithCancellationCheck(),
        new RegularJob(2),
    ],
]);

// Not flagged: every job uses Batchable.
Bus::batch([
    new BatchableJobWithCancellationCheck(),
    new BatchableJobWithoutCancellationCheck(),
]);

// Not flagged: not a queued job, so not this rule's concern.
Bus::batch([
    new BatchableNonQueuedClass(),
]);

// Not flagged: only array literals are inspected element by element.
/** @var list<RegularJob> $jobs */
$jobs = [];

Bus::batch($jobs);

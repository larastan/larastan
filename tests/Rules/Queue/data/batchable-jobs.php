<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\SkipIfBatchCancelled;

class BatchableNonQueuedClass
{
    use Batchable;

    public function handle(): void
    {
    }
}

abstract class AbstractBatchableJob implements ShouldQueue
{
    use Batchable;

    public function handle(): void
    {
    }
}

class BatchableJobWithCancellationCheck implements ShouldQueue
{
    use Batchable;
    use Dispatchable;

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
    }
}

class BatchableJobWithSkipMiddleware implements ShouldQueue
{
    use Batchable;

    /** @return list<object> */
    public function middleware(): array
    {
        return [new SkipIfBatchCancelled()];
    }

    public function handle(): void
    {
    }
}

class BatchableJobBase implements ShouldQueue
{
    use Batchable;

    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
    }
}

class BatchableJobSubclass extends BatchableJobBase
{
}

class BatchableJobWithoutCancellationCheck implements ShouldQueue
{
    use Batchable;
    use Dispatchable;

    public function handle(): void
    {
    }
}

class ConcreteBatchableJobFromAbstractBase extends AbstractBatchableJob
{
}

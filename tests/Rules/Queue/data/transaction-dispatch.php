<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

use function dispatch;

class NotifyOwner implements ShouldQueue
{
    use Dispatchable;

    public function __construct(public int $productId)
    {
    }
}

class NotifyOwnerAfterCommit implements ShouldQueue
{
    use Dispatchable;

    public bool $afterCommit = true;

    public function __construct(public int $productId)
    {
    }
}

class PlainDispatchable
{
    use Dispatchable;
}

DB::transaction(static function (): void {
    NotifyOwner::dispatch(1);
});

DB::transaction(static function (): void {
    dispatch(new NotifyOwner(1));
});

DB::transaction(static function (): void {
    NotifyOwner::dispatch(1)->onQueue('default');
});

DB::transaction(static fn (): mixed => NotifyOwner::dispatch(1));

// Not flagged: the dispatch is deferred explicitly.
DB::transaction(static function (): void {
    NotifyOwner::dispatch(1)->afterCommit();
});

DB::transaction(static function (): void {
    NotifyOwner::dispatch(1)->onQueue('default')->afterCommit();
});

// Not flagged: the job opts in for every dispatch.
DB::transaction(static function (): void {
    NotifyOwnerAfterCommit::dispatch(1);
});

// Not flagged: synchronous dispatch runs inline, inside the transaction.
DB::transaction(static function (): void {
    NotifyOwner::dispatchSync(1);
});

// Not flagged: the Bus facade is a different entry point.
DB::transaction(static function (): void {
    Bus::dispatch(new NotifyOwner(1));
});

// Not flagged: a non queued dispatchable runs synchronously.
DB::transaction(static function (): void {
    PlainDispatchable::dispatch();
});

// Not flagged: outside any transaction.
NotifyOwner::dispatch(1);

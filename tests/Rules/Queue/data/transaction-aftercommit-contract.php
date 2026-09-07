<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

class AfterCommitContractJob implements ShouldQueueAfterCommit
{
    use Dispatchable;
    use Queueable;
}

class BeforeCommitContractJob extends AfterCommitContractJob
{
    public $afterCommit = false;
}

DB::transaction(static function (): void {
    AfterCommitContractJob::dispatch();
    AfterCommitContractJob::dispatch()->beforeCommit();
    AfterCommitContractJob::dispatch()->beforeCommit()->afterCommit();
    BeforeCommitContractJob::dispatch();
    BeforeCommitContractJob::dispatch()->afterCommit();
});

<?php

declare(strict_types=1);

namespace Tests\Rules\Queue\Data;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RegularJob implements ShouldQueue
{
    use Dispatchable;

    public function __construct(public int $productId)
    {
    }

    public function handle(): void
    {
    }
}

abstract class AbstractUniqueJob implements ShouldQueue, ShouldBeUnique
{
    public function __construct(public int $companyId)
    {
    }
}

class UniqueJobWithUniqueForProperty implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;

    public int $uniqueFor = 3600;
}

class UniqueJobWithUniqueForMethod implements ShouldQueue, ShouldBeUnique
{
    public function uniqueFor(): int
    {
        return 3600;
    }
}

class UniqueJobWithoutUniqueFor implements ShouldQueue, ShouldBeUnique
{
    public function uniqueId(): string
    {
        return 'constant';
    }
}

class UniqueJobInheritingUniqueFor extends AbstractUniqueJob
{
    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }
}

class ParameterizedUniqueJobWithoutUniqueId implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId)
    {
    }
}

class ParameterizedUniqueJobWithUniqueIdMethod implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function __construct(public int $companyId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->companyId;
    }
}

class ParameterizedUniqueJobWithUniqueIdProperty implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public string $uniqueId = 'fixed';

    public function __construct(public int $companyId)
    {
    }
}

class UniqueJobWithParameterlessConstructor implements ShouldQueue, ShouldBeUnique
{
    public int $uniqueFor = 3600;

    public function __construct()
    {
    }
}

class UniqueUntilProcessingJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    public function __construct(public int $companyId)
    {
    }
}

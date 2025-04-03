<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

class EnvUsageClass
{
    use EnvUsageTrait;

    public function test(): void
    {
        $this->callEnv();
    }
}

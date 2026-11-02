<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

class EnvUsageClassOverride
{
    use EnvUsageTrait;

    public function test(): void
    {
        $this->callEnv();
    }

    protected function callEnv(): void
    {
        //
    }
}

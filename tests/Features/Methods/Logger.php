<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use Monolog\Handler\HandlerInterface;

class Logger
{
    /** @phpstan-return HandlerInterface[] */
    public function testGetHandlers(\Illuminate\Log\Logger $logger): array
    {
        return $logger->getHandlers();
    }
}

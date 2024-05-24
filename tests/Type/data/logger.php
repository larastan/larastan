<?php

namespace Logger;

use Illuminate\Log\Logger;

use function PHPStan\Testing\assertType;

function test(Logger $logger): void
{
    assertType('array<int, Monolog\Handler\HandlerInterface>', $logger->getHandlers());
}

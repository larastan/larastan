<?php

namespace Logger;

use function PHPStan\Testing\assertType;

function doFoo(\Illuminate\Log\Logger $logger)
{
    assertType('array<int, Monolog\Handler\HandlerInterface>', $logger->getHandlers());
}

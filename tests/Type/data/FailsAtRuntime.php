<?php

declare(strict_types=1);

class FailsAtRuntime
{
    public function __construct()
    {
        throw new \Exception("Cannot resolve class dynamically.");
    }
}

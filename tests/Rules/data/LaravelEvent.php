<?php

declare(strict_types=1);

namespace Tests\Rules\data;

use Illuminate\Foundation\Events\Dispatchable;

class LaravelEvent
{
    use Dispatchable;

    /** @var string */
    private $foo;

    /** @var int */
    private $bar;

    public function __construct(string $foo, int $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
}

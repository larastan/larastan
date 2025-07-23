<?php

namespace Larastan\Larastan\Tests\Rules\ConsoleCommand\data;

use Illuminate\Console\Command;

class CommandWithConstructorDI extends Command
{
    public function __construct(private SomeService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return 0;
    }
}

class CommandWithoutConstructor extends Command
{
    public function handle(): int
    {
        return 0;
    }
}

class CommandWithEmptyConstructor extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return 0;
    }
}

class CommandWithMultipleConstructorArgs extends Command
{
    public function __construct(
        private SomeService $service,
        private AnotherService $anotherService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        return 0;
    }
}

abstract class AbstractCommand extends Command
{
    public function __construct(private SomeService $service)
    {
        parent::__construct();
    }
}

class CommandWithMethodInjection extends Command
{
    public function handle(SomeService $service, AnotherService $anotherService): int
    {
        return 0;
    }
}

class SomeService
{
}

class AnotherService
{
}
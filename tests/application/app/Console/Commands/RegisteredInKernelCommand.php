<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function PHPStan\Testing\assertType;

class RegisteredInKernelCommand extends Command
{
    protected $signature = 'registered-in-kernel {argument} {--option=}';

    protected $description = 'Dummy command registered through the $commands property of the console kernel.';

    public function handle(): void
    {
        assertType('array{argument: string}', $this->argument());
        assertType('string', $this->argument('argument'));
        assertType('string|null', $this->option('option'));

        assertType('true', $this->hasArgument('argument'));
        assertType('false', $this->hasArgument('undefinedArgument'));
        assertType('true', $this->hasOption('option'));
    }

    public function undefinedInput(): void
    {
        $this->argument('undefinedArgument');
        $this->option('undefinedOption');
    }
}

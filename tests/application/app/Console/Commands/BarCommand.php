<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function PHPStan\Testing\assertType;

class BarCommand extends Command
{
    protected $signature = 'bar {argumentArray*} {--optionArray=*}';

    protected $description = 'Dummy command used to test console command types.';

    public function handle(): void
    {
        assertType('array{argumentArray: array<int, string>}', $this->argument());
        assertType('array{argumentArray: array<int, string>}', $this->arguments());
        assertType('array<int, string>', $this->argument('argumentArray'));

        assertType('true', $this->hasArgument('argumentArray'));

//        assertType('array{optionArray: array<int, string|null>, help: bool, quiet: bool, verbose: bool, version: bool, ansi: bool|null, no-interaction: bool, env: string|null}', $this->options());
//        assertType('array{optionArray: array<int, string|null>, help: bool, quiet: bool, verbose: bool, version: bool, ansi: bool|null, no-interaction: bool, env: string|null}', $this->option());
        assertType('array<int, string|null>', $this->option('optionArray'));

        assertType('true', $this->hasOption('optionArray'));
    }
}

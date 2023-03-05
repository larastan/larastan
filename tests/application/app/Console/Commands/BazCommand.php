<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function PHPStan\Testing\assertType;

class BazCommand extends Command
{
    protected $signature = 'baz {optionalArgumentArray?*}';

    protected $description = 'Dummy command used to test console command types.';

    public function handle(): void
    {
        assertType('array{optionalArgumentArray: array<int, string>}', $this->argument());
        assertType('array{optionalArgumentArray: array<int, string>}', $this->arguments());
        assertType('array<int, string>', $this->argument('optionalArgumentArray'));

        assertType('true', $this->hasArgument('optionalArgumentArray'));
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use function PHPStan\Testing\assertType;

class FooCommand extends Command
{
    protected $signature = 'foo {argument} {optionalArg?} {optionalArgWithDefault=1} {--O|option} {--optionWithValue=}';

    protected $description = 'Dummy command used to test console command types.';

    public function handle(): void
    {
        assertType('array{argument: string, optionalArg: string|null, optionalArgWithDefault: string}', $this->argument());
        assertType('array{argument: string, optionalArg: string|null, optionalArgWithDefault: string}', $this->arguments());
        assertType('string', $this->argument('argument'));
        assertType('string|null', $this->argument('optionalArg'));
        assertType('string', $this->argument('optionalArgWithDefault'));
        assertType('array|bool|string|null', $this->argument('foobar'));

        assertType('true', $this->hasArgument('argument'));
        assertType('true', $this->hasArgument('optionalArg'));
        assertType('true', $this->hasArgument('optionalArgWithDefault'));
        assertType('false', $this->hasArgument('foobar'));

//        assertType('array{option: bool, optionWithValue: string|null, help: bool, quiet: bool, verbose: bool, version: bool, ansi: bool|null, no-interaction: bool, env: string|null}', $this->options());
//        assertType('array{option: bool, optionWithValue: string|null, help: bool, quiet: bool, verbose: bool, version: bool, ansi: bool|null, no-interaction: bool, env: string|null}', $this->option());
        assertType('bool', $this->option('O'));
        assertType('bool', $this->option('option'));
        assertType('string|null', $this->option('optionWithValue'));
        assertType('bool', $this->option('help'));
        assertType('bool', $this->option('v'));
        assertType('array|bool|string|null', $this->option('foobar'));

        assertType('true', $this->hasOption('O'));
        assertType('true', $this->hasOption('option'));
        assertType('true', $this->hasOption('optionWithValue'));
        assertType('true', $this->hasOption('help'));
        assertType('true', $this->hasOption('v'));
        assertType('false', $this->hasOption('foobar'));
    }
}

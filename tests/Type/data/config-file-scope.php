<?php

namespace ConfigFileScope;

use function PHPStan\Testing\assertType;

function callerWithSameVariable(): void
{
    $hosts = 123;

    assertType('non-empty-list<string>', config('scopevars.hosts'));
}

function callerWithoutVariable(): void
{
    assertType('non-empty-list<string>', config('scopevars.hosts'));
}

function phpDocUsesConfigFileImports(): void
{
    assertType('Illuminate\Support\Collection<int, string>', config('scopedoc.items'));
}

function returnInsideClosureIsNotTheConfig(): void
{
    assertType('lowercase-string', config('scopeclosure.v'));
}

function conditionalReturnHasNoSingleValue(): void
{
    assertType('mixed', config('scopeconditional.v'));
}

function variableFromIncludeIsUnknown(): void
{
    assertType('mixed', config('scopeinclude.v'));
}

function selfReference(): void
{
    assertType("'larastan'", config('scopeself.name'));
    assertType('mixed', config('scopeself.early'));
    assertType("'larastan'", config('scopeself.late'));
}

function readsOtherConfigFile(): void
{
    assertType("'leaf'", config('scopereader.v'));
    assertType("'leaf'", config('scopereader.inline'));
}

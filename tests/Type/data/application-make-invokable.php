<?php

namespace ApplicationMakeInvokable;

use Illuminate\Support\Collection;

use function PHPStan\Testing\assertType;

class InvokableAction
{
    public function __invoke(): Collection
    {
        return new Collection();
    }
}

function test(): void
{
    assertType('Illuminate\Support\Application', app());
    assertType('InvokableAction', app(InvokableAction::class));
    assertType('Illuminate\Support\Collection', app(InvokableAction::class)->__invoke());
    assertType('Illuminate\Support\Collection', app(InvokableAction::class)());
}

<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Support\Facades\Gate;

class GateFacade
{
    public function testGateForUser(): \Illuminate\Contracts\Auth\Access\Gate
    {
        return Gate::forUser(new User());
    }

    public function testGateInspect(): \Illuminate\Auth\Access\Response
    {
        return Gate::inspect('foo');
    }

    public function testGateHas(): bool
    {
        return Gate::has('foo');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use App\User;

class PaginatorExtension
{
    public function testPaginate(): array
    {
        return User::paginate()->all();
    }

    public function testSimplePaginate(): array
    {
        return User::simplePaginate()->all();
    }
}

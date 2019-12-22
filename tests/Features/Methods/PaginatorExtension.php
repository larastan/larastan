<?php

declare(strict_types=1);

namespace Tests\Features\Methods;

use App\User;

class PaginatorExtension
{
    /**
     * @return array<int, User>
     */
    public function testPaginateProxiesToCollection(): array
    {
        return User::paginate()->all();
    }

    /**
     * @return array<int, User>
     */
    public function testSimplePaginateProxiesToCollection(): array
    {
        return User::simplePaginate()->all();
    }
}

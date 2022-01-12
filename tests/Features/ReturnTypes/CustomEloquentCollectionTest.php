<?php

namespace Tests\Features\ReturnTypes;

use App\User;
use Illuminate\Database\Eloquent\Collection;

class CustomEloquentCollectionTest
{
    /**
     * @phpstan-return Collection<int, User>
     */
    public function testEloquentCollectionWhereReturnsEloquentCollection(): Collection
    {
        return (new User)->children->where('active', true);
    }
}

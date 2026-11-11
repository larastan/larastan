<?php

namespace PassthruL13301;

use App\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('Illuminate\Support\Collection', User::query()->insertOrIgnoreReturning([]));
}

class Post extends Model
{
}

/** @extends Builder<Post> */
class PassthruBuilder extends Builder
{
    public function test(): void
    {
        assertType('Illuminate\Support\Collection', $this->insertOrIgnoreReturning([]));
    }
}

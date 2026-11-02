<?php

namespace ModelScopeAttribute;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

/** @param  Builder<Activity>  $query */
function test(Builder $query): void
{
    assertType('Illuminate\Database\Eloquent\Builder<ModelScopeAttribute\Activity>', Activity::completed());
    assertType('Illuminate\Database\Eloquent\Builder<ModelScopeAttribute\Activity>', $query->completed());
    assertType('ModelScopeAttribute\Activity', $query->completed(false)->firstOrFail());
    assertType('Illuminate\Database\Eloquent\Builder<ModelScopeAttribute\Activity>', Activity::withVoidReturn());
    assertType('Illuminate\Database\Eloquent\Builder<ModelScopeAttribute\Activity>', $query->withVoidReturn());
    assertType('ModelScopeAttribute\Activity', $query->withVoidReturn()->firstOrFail());
    assertType('*ERROR*', $query->notAScope());
}

class Activity extends Model
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    #[Scope]
    protected function completed(Builder $query, bool $completed = true): Builder
    {
        return $query->where('completed', $completed);
    }

    /** @param  Builder<static>  $query */
    #[Scope]
    protected function withVoidReturn(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }

    /** @param  Builder<static>  $query */
    #[Scope]
    public function notAScope(Builder $query): void
    {
        $query->where('whyuse', 'void');
    }
}

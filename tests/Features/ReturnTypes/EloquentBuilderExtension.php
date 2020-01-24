<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EloquentBuilderExtension
{
    public function testModelScopeReturnsBuilder(): Builder
    {
        return TestScopesModel::query()
            ->foo('piet');
    }

    public function testCustomBuilderMethodReturnsBuilder(): CustomBuilder
    {
        return TestScopesModel::query()
            ->type('piet');
    }
}

class TestScopesModel extends Model
{
    public function scopeFoo(Builder $query, string $foo): Builder
    {
        return $query->where(['foo' => $foo]);
    }

    /**
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Tests\Features\ReturnTypes\CustomBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomBuilder($query);
    }
}

class CustomBuilder extends Builder
{
    public function type(string $type): void
    {
        $this->where(['type' => $type]);
    }
}

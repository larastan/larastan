<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class EloquentBuilderExtension
{
    public function testModelScopeReturnsBuilder(): Builder
    {
        return EloquentBuilderModel::query()
            ->foo('piet');
    }

    public function testCustomBuilderMethodReturnsBuilder(): CustomBuilder
    {
        return EloquentBuilderModel::query()
            ->type('piet');
    }

    public function testAModelCreationMethodReturnsModel(): EloquentBuilderModel
    {
        return EloquentBuilderModel::query()
            ->where(['foo' => 'bar'])
            ->make();
    }

    public function testAModelRetrievalMethodReturnsModel(): EloquentBuilderModel
    {
        return EloquentBuilderModel::query()
            ->where(['foo' => 'bar'])
            ->firstOrFail();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<\Tests\Features\ReturnTypes\EloquentBuilderModel>
     */
    public function testGetMethodReturnType(): Collection
    {
        return EloquentBuilderModel::query()
            ->where(['foo' => 'bar'])
            ->get();
    }
}

class EloquentBuilderModel extends Model
{
    public function scopeFoo(string $foo)
    {
        $this->where(['foo' => $foo]);
    }

    /**
     * @return \Tests\Features\ReturnTypes\CustomBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new CustomBuilder($query);
    }
}

class CustomBuilder extends Builder
{
    public function type(string $type)
    {
        $this->where(['type' => $type]);
    }
}

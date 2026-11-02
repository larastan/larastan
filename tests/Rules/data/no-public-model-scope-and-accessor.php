<?php

declare(strict_types=1);

namespace Tests\Rules\Data;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;

class TestModel extends Model
{
    protected function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true);
    }

    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucfirst($value),
            set: fn (string $value) => strtolower($value),
        );
    }

    public function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucfirst($value),
            set: fn (string $value) => strtolower($value),
        );
    }

    #[Scope]
    protected function withAttribute(Builder $query): Builder
    {
        return $query->where('with_attribute', true);
    }

    #[Scope]
    public function publicWithAttribute(Builder $query): Builder
    {
        return $query->where('public_with_attribute', true);
    }

    public function scopeInvalid(): string
    {
        return 'invalid';
    }

    public function regularMethod(): string
    {
        return 'regular';
    }

    private function scopePrivate(Builder $query): Builder
    {
        return $query->where('private', true);
    }

    private function privateAccessor(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $value,
        );
    }
}

class NonModelClass
{
    public function scopeIgnored(Builder $query): Builder
    {
        return $query;
    }

    public function accessorIgnored(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => $value,
        );
    }
}

class ChildModel extends TestModel
{
    /**
     * @param Builder<TestModel> $query
     * @return Builder<TestModel>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('public', true);
    }
}

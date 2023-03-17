<?php

declare(strict_types=1);

namespace Database\Factories;

/**
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @mixin \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 *
 * @property TModel $model
 */
class FactoryMacros
{
    /**
     * @param  array<model-property<TModel>, mixed>  $attributes
     * @param  array<model-property<TModel>, mixed>  $values
     * @return \Closure(array<model-property<TModel>, mixed>, array<model-property<TModel>, mixed>):TModel
     */
    public function updateOrCreate(array $attributes = [], array $values = []): \Closure
    {
        return function (array $attributes = [], array $values = []) {
            if ($instance = $this->model::where($attributes)->first()) {
                $instance->update($values);

                return $instance;
            }

            return $this->create(array_merge($attributes, $values));
        };
    }
}
